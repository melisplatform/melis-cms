import { useCallback, useEffect, useRef, useState } from 'react'

/**
 * Scroll infini + tri server-side + pagination keyset, mutualisé pour les listes
 * natives MelisCore (Users, Logs, Platform, Language, Annonces…).
 *
 * Le backend (cf. MelisReactKeysetListTrait) renvoie `{ items, total, nextCursor }` ;
 * `nextCursor` (opaque) est renvoyé tel quel en `after` pour charger le lot suivant,
 * `null` = fin de liste. Les filtres sont capturés par la closure `fetcher` et
 * déclenchent un rechargement frais via `deps`. Le tri est server-side : changer de
 * colonne/sens relance depuis le début (plus aucun tri client sur le sous-ensemble).
 *
 * Garde anti-course : un req-id invalide les réponses périmées (filtre/tri changé en
 * cours), `loadingRef` empêche d'empiler les « load more ».
 */
export interface KeysetPage<T> { items: T[]; total: number; nextCursor: string | null }

export interface KeysetFetchArgs {
  limit: number
  sort: string
  dir: 'asc' | 'desc'
  after?: string
}

export interface UseKeysetListOptions<T> {
  fetcher: (a: KeysetFetchArgs) => Promise<KeysetPage<T>>
  /** Valeurs de filtres : tout changement relance un chargement frais. */
  deps: unknown[]
  limit?: number
  defaultSort?: string
  defaultDir?: 'asc' | 'desc'
  /** État restauré depuis un cache module-level (navigation). */
  initial?: {
    items: T[]; total: number; cursor: string | null; hasMore: boolean
    sortCol: string; sortDir: 'asc' | 'desc'
  }
  /** true → ne pas relancer de fetch au 1er montage (cache déjà peuplé). */
  skipInitial?: boolean
}

export function useKeysetList<T>(opts: UseKeysetListOptions<T>) {
  const LIMIT = opts.limit ?? 25
  const [items, setItems]   = useState<T[]>(opts.initial?.items ?? [])
  const [total, setTotal]   = useState(opts.initial?.total ?? 0)
  const [loading, setLoading] = useState(false)
  const [hasMore, setHasMore] = useState(opts.initial?.hasMore ?? false)
  const [sortCol, setSortCol] = useState(opts.initial?.sortCol ?? opts.defaultSort ?? 'id')
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>(opts.initial?.sortDir ?? opts.defaultDir ?? 'desc')

  const cursorRef  = useRef<string | null>(opts.initial?.cursor ?? null)
  const loadingRef = useRef(false)
  const reqIdRef   = useRef(0)
  const sentinelRef = useRef<HTMLDivElement>(null)

  // La closure `fetcher` capture les filtres courants ; on garde toujours la dernière.
  const fetcherRef = useRef(opts.fetcher)
  fetcherRef.current = opts.fetcher

  const runLoad = useCallback(async (reset: boolean) => {
    if (!reset && loadingRef.current) return
    const myReq = ++reqIdRef.current
    loadingRef.current = true
    setLoading(true)
    const after = reset ? undefined : (cursorRef.current ?? undefined)
    try {
      const res = await fetcherRef.current({ limit: LIMIT, sort: sortCol, dir: sortDir, after })
      if (myReq !== reqIdRef.current) return
      cursorRef.current = res.nextCursor
      setHasMore(res.nextCursor !== null)
      setTotal(res.total)
      setItems((prev) => (reset ? res.items : [...prev, ...res.items]))
    } catch { /* silent */ }
    finally {
      if (myReq === reqIdRef.current) { setLoading(false); loadingRef.current = false }
    }
  }, [sortCol, sortDir, LIMIT])

  // Chargement frais quand filtres/tri changent. 1er montage avec cache → on saute.
  const didInitRef = useRef(false)
  useEffect(() => {
    if (!didInitRef.current) {
      didInitRef.current = true
      if (opts.skipInitial) return
    }
    runLoad(true)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [...opts.deps, sortCol, sortDir])

  // Scroll infini : sentinel visible → lot suivant (runLoad gère l'anti-stack).
  useEffect(() => {
    if (!sentinelRef.current || !hasMore) return
    const obs = new IntersectionObserver(
      ([entry]) => { if (entry.isIntersecting) runLoad(false) },
      { rootMargin: '120px' },
    )
    obs.observe(sentinelRef.current)
    return () => obs.disconnect()
  }, [hasMore, runLoad])

  const toggleSort = useCallback((id: string) => {
    setSortCol((cur) => {
      if (cur === id) { setSortDir((d) => (d === 'asc' ? 'desc' : 'asc')); return cur }
      setSortDir(id === 'id' ? 'desc' : 'asc')
      return id
    })
  }, [])

  /** Force un rechargement depuis le début (refresh / reset filtres). */
  const reload = useCallback(() => { cursorRef.current = null; runLoad(true) }, [runLoad])

  /** Retire un élément localement (après delete) sans recharger. */
  const removeLocal = useCallback((pred: (it: T) => boolean) => {
    setItems((prev) => prev.filter((it) => !pred(it)))
    setTotal((t) => Math.max(0, t - 1))
  }, [])

  /** Snapshot pour le cache module-level. */
  const snapshot = () => ({ items, total, cursor: cursorRef.current, hasMore, sortCol, sortDir })

  return {
    items, setItems, total, loading, hasMore, sentinelRef,
    sortCol, sortDir, setSortCol, setSortDir, toggleSort,
    reload, removeLocal, snapshot,
  }
}
