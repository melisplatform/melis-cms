import { useRef, useState } from 'react'

/**
 * Touch-compatible drag-and-drop for two-panel (Masquées/Visibles) column managers (ColManager).
 *
 * The native HTML5 Drag and Drop API (`draggable` + dragstart/dragover/drop) never fires from
 * touch input in ANY browser — it's mouse-only by spec. Uses plain mousedown/touchstart (not the
 * newer Pointer Events API, whose support is less universal on older mobile browsers/webviews)
 * to track the SAME gesture for both mouse and touch.
 *
 * Usage: each draggable row gets `data-col-item={col.id}` + `onMouseDown`/`onTouchStart` wired to
 * `startDragMouse(col.id)`/`startDragTouch(col.id)` + `style={{ touchAction: 'none' }}` (required
 * so the browser doesn't hijack the gesture as a page scroll before our JS sees it); each panel
 * container gets `data-col-panel="hidden"` or `"visible"`. Hit-testing during the drag uses
 * `document.elementFromPoint` + `.closest()` on those data attributes.
 */
export interface ColDef { id: string; visible: boolean }
export type DropTarget = { id: string; panel: 'visible' | 'hidden' }

export function useDragReorder({ cols, onChange }: { cols: ColDef[]; onChange: (cols: ColDef[]) => void }) {
  const [draggingId, setDraggingId] = useState<string | null>(null)
  const [overTarget, setOverTarget] = useState<DropTarget | null>(null)
  // Position of the mouse/finger while dragging — consumers render a small floating chip that
  // follows it. On touch there's no cursor and no native drag-ghost, so without SOME element
  // visibly tracking the finger the interaction can read as "not working" even if it is.
  const [dragPos, setDragPos] = useState<{ x: number; y: number } | null>(null)

  const colsRef = useRef(cols); colsRef.current = cols
  const onChangeRef = useRef(onChange); onChangeRef.current = onChange
  const draggingRef = useRef<string | null>(null)
  const overRef = useRef<DropTarget | null>(null)
  const active = useRef<{ move: (e: Event) => void; up: (e: Event) => void; cancel: (e: Event) => void } | null>(null)

  function commitDrop(target: DropTarget, dragId: string) {
    const cur = colsRef.current
    const shown = cur.filter((c) => c.visible)
    const hidden = cur.filter((c) => !c.visible)
    const srcItem = cur.find((c) => c.id === dragId)
    if (!srcItem) return
    const updatedItem = { ...srcItem, visible: target.panel === 'visible' }
    let vList = shown.filter((c) => c.id !== dragId)
    const hList = hidden.filter((c) => c.id !== dragId)
    if (target.panel === 'visible') {
      const dstId = target.id
      if (dstId === '__panel__') vList = [...vList, updatedItem]
      else {
        const idx = vList.findIndex((c) => c.id === dstId)
        vList = idx === -1 ? [...vList, updatedItem] : [...vList.slice(0, idx), updatedItem, ...vList.slice(idx)]
      }
      onChangeRef.current([...vList, ...hList])
    } else {
      onChangeRef.current([...vList, ...hList, updatedItem])
    }
  }

  function endDrag(commit: boolean) {
    const dragId = draggingRef.current
    const target = overRef.current
    if (active.current) {
      document.removeEventListener('mousemove', active.current.move)
      document.removeEventListener('mouseup', active.current.up)
      document.removeEventListener('touchmove', active.current.move)
      document.removeEventListener('touchend', active.current.up)
      document.removeEventListener('touchcancel', active.current.cancel)
      active.current = null
    }
    draggingRef.current = null
    overRef.current = null
    setDraggingId(null)
    setOverTarget(null)
    setDragPos(null)
    if (commit && dragId && target) commitDrop(target, dragId)
  }

  function hitTest(x: number, y: number) {
    const el = document.elementFromPoint(x, y) as HTMLElement | null
    const itemEl = el?.closest<HTMLElement>('[data-col-item]') ?? null
    const panelEl = el?.closest<HTMLElement>('[data-col-panel]') ?? null
    let next: DropTarget | null = null
    if (itemEl && itemEl.dataset.colItem !== draggingRef.current) {
      const panel = itemEl.closest<HTMLElement>('[data-col-panel]')?.dataset.colPanel as 'visible' | 'hidden' | undefined
      if (panel) next = { id: itemEl.dataset.colItem!, panel }
    } else if (panelEl) {
      next = { id: '__panel__', panel: panelEl.dataset.colPanel as 'visible' | 'hidden' }
    }
    if (next?.id !== overRef.current?.id || next?.panel !== overRef.current?.panel) {
      overRef.current = next
      setOverTarget(next)
    }
  }

  function beginDrag(colId: string, x: number, y: number) {
    draggingRef.current = colId
    overRef.current = null
    setDraggingId(colId)
    setDragPos({ x, y })
  }

  /** Mouse path — desktop. */
  function startDragMouse(colId: string) {
    return (e: React.MouseEvent) => {
      if (e.button !== 0) return
      e.preventDefault()
      beginDrag(colId, e.clientX, e.clientY)

      const onMove = (ev: Event) => {
        const me = ev as MouseEvent
        setDragPos({ x: me.clientX, y: me.clientY })
        hitTest(me.clientX, me.clientY)
      }
      const onUp = () => endDrag(true)
      active.current = { move: onMove, up: onUp, cancel: onUp }
      document.addEventListener('mousemove', onMove)
      document.addEventListener('mouseup', onUp)
    }
  }

  /** Touch path — mobile. Plain Touch Events (not Pointer Events), for maximum compatibility
   *  with older mobile Safari/WebView versions that may not fully support Pointer Events. */
  function startDragTouch(colId: string) {
    return (e: React.TouchEvent) => {
      const t = e.touches[0]
      if (!t) return
      e.preventDefault()
      beginDrag(colId, t.clientX, t.clientY)

      const onMove = (ev: Event) => {
        const te = ev as TouchEvent
        const touch = te.touches[0]
        if (!touch) return
        if (te.cancelable) te.preventDefault()
        setDragPos({ x: touch.clientX, y: touch.clientY })
        hitTest(touch.clientX, touch.clientY)
      }
      const onEnd = () => endDrag(true)
      const onCancel = () => endDrag(false)
      active.current = { move: onMove, up: onEnd, cancel: onCancel }
      document.addEventListener('touchmove', onMove, { passive: false })
      document.addEventListener('touchend', onEnd)
      document.addEventListener('touchcancel', onCancel)
    }
  }

  return { draggingId, overTarget, dragPos, startDragMouse, startDragTouch }
}
