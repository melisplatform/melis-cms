/**
 * CANONICAL error-handling primitives for the React back-office — ONE module, copied verbatim
 * into every brick's `src/shared/` (bricks cannot import host modules, so the file is duplicated;
 * melis-core is the source of truth). Goal: unify a UX that used to be inconsistent (sometimes a
 * toast, sometimes an inline red field, sometimes a top banner, sometimes nothing).
 *
 * Two surfaces, one shape (`FormIssue[]`):
 *   1. <FormErrorBanner> — a red banner AT THE TOP OF A FORM/MODAL that states the problem AND
 *      LISTS every field that is missing or invalid. This is the platform-standard for save/submit
 *      validation (the design chosen in ticket "unify error handling"). Fields stay highlighted
 *      inline as well; the banner is the scannable summary above the fold.
 *   2. koNotify/okNotify — a one-shot toast (top-right BO chrome) for outcomes with NO inline field
 *      to point at (save succeeded, a network/unexpected error, an action result). koNotify can also
 *      carry an issue list — the host toast (melis-core Notifications.tsx) renders `fields`.
 *
 * Everything is self-contained: only React is imported, all colours come from the host theme CSS
 * variables (works light/dark, in any brick, no Tailwind dependency). `collectIssues()` normalises
 * the several error shapes Melis produces (plain string, string[], `{field: message}` maps, and the
 * MelisCore formatErrors `{field:{validatorKey:msg,label}}` shape) into a single `FormIssue[]`.
 */

import type { CSSProperties, ReactNode } from 'react'

export interface FormIssue {
  /** Human label of the field/section in error (optional — omit for a bare message). */
  label?: string
  /** The message to show. */
  message: string
}

/* ── Toast bridge ────────────────────────────────────────────────────────────
 * Same window-message contract the buildToolPage bridge uses for legacy tools → the host
 * Notifications component de-dups + renders (green ok / red ko), including an optional field list. */
function postNotif(kind: 'ok' | 'ko', title: string, message: string, issues?: FormIssue[]) {
  try {
    const fields = (issues ?? [])
      .filter((i) => i && i.label)
      .map((i) => ({ label: i.label as string, messages: [i.message] }))
    window.postMessage({ __melisNotif: true, kind, title, message, fields }, '*')
  } catch { /* no-op (e.g. SSR / sandboxed frame) */ }
}

export function okNotify(title: string, message = ''): void { postNotif('ok', title, message) }
/** Error toast. Pass `issues` to list offending fields inside the toast (host renders them). */
export function koNotify(title: string, message = '', issues?: FormIssue[]): void { postNotif('ko', title, message, issues) }

/* ── Normalisation ───────────────────────────────────────────────────────────
 * Any error payload Melis can hand back → a flat FormIssue[]. */

// First human-readable message inside one MelisCore formatErrors entry
// (e.g. { isEmpty: "The input is required…", label: "Input Label" }); skips `label`/`form` meta keys.
function firstMessage(entry: unknown): string {
  if (entry == null) return ''
  if (typeof entry === 'string') return entry
  if (Array.isArray(entry)) return firstMessage(entry[0])
  if (typeof entry === 'object') {
    const hit = Object.entries(entry as Record<string, unknown>).find(([k]) => k !== 'label' && k !== 'form')
    return hit ? firstMessage(hit[1]) : ''
  }
  return String(entry)
}

/**
 * Normalise an error payload into FormIssue[]. Accepts:
 *  - a plain string            → [{ message }]
 *  - a string[]                → one issue each
 *  - a FormIssue[]             → passthrough (already normalised)
 *  - `{ field: "message" }`    → [{ label: field, message }]   (e.g. newsletter `errors`)
 *  - MelisCore formatErrors    → `{ massd_text: { isEmpty: "…", label: "Input Label" } }`
 *                                → [{ label: "Input Label", message: "…" }]
 * The optional `labels` map renames a raw field key to a display label (server key → UI label).
 */
export function collectIssues(
  input: unknown,
  labels: Record<string, string> = {},
): FormIssue[] {
  if (input == null || input === '') return []
  if (typeof input === 'string') return [{ message: input }]
  if (Array.isArray(input)) {
    return input
      .map((v) => (typeof v === 'string' ? { message: v } : (v as FormIssue)))
      .filter((i) => i && (i.message || i.label))
  }
  if (typeof input === 'object') {
    const out: FormIssue[] = []
    for (const [field, entry] of Object.entries(input as Record<string, unknown>)) {
      if (field === 'label' || field === 'form' || entry == null) continue
      const message = firstMessage(entry)
      if (!message) continue
      // Prefer an explicit label from the entry, then the caller map, then the raw key.
      const entryLabel = entry && typeof entry === 'object' ? (entry as { label?: string }).label : undefined
      out.push({ label: labels[field] ?? entryLabel ?? field, message })
    }
    return out
  }
  return []
}

/**
 * Map each errored field → its first message, keyed by the SERVER field name — for colouring that
 * field's own input/label red inline. Complements the banner (kept for back-compat with callers that
 * highlight fields one by one). Same source data as collectIssues.
 */
export function fieldErrorMap(errors?: unknown): Record<string, string> {
  const out: Record<string, string> = {}
  if (!errors || typeof errors !== 'object' || Array.isArray(errors)) return out
  for (const [field, entry] of Object.entries(errors as Record<string, unknown>)) {
    if (field === 'label' || field === 'form' || entry == null) continue
    out[field] = firstMessage(entry)
  }
  return out
}

/* ── Banner component ────────────────────────────────────────────────────────
 * Theme-aware via CSS vars, red error accent. Renders null when there is nothing to show. */

const box: CSSProperties = {
  border: '1px solid color-mix(in srgb, #ef4444 45%, var(--color-border,#e5e7eb))',
  background: 'color-mix(in srgb, #ef4444 10%, var(--color-card,#fff))',
  color: '#dc2626',
  borderRadius: 8,
  padding: '10px 14px',
  fontSize: 14,
  lineHeight: 1.45,
}
const listCss: CSSProperties = { margin: '6px 0 0', padding: '0 0 0 18px', display: 'flex', flexDirection: 'column', gap: 2 }

/**
 * Standard form-error banner. Show it above a form/modal on a failed save/submit.
 *  - `title`   headline (caller-provided → i18n stays with the caller). Defaults to a generic English
 *              line; every real caller should pass its own translated string.
 *  - `issues`  the missing/invalid fields to list. Pass anything `collectIssues` accepts OR a
 *              ready FormIssue[]; a bare string is treated as a single message.
 *  - `icon`    optional leading node (e.g. an alert glyph).
 *  - `html`    when set, the caller vouches that `title` and each issue `message` carry TRUSTED
 *              HTML (e.g. Melis service messages that embed `<b>path</b>`) → the markup is rendered
 *              instead of escaped. Default false (safe text). Labels are our own i18n and are always
 *              rendered as text. Only pass `html` for server/legacy messages you know are trusted —
 *              it is a dangerouslySetInnerHTML sink; never enable it for free user input.
 * When there are no issues and no title, renders nothing.
 */
export function FormErrorBanner({
  title,
  issues,
  icon,
  html,
  style,
}: {
  title?: string
  issues?: unknown
  icon?: ReactNode
  html?: boolean
  style?: CSSProperties
}): ReactNode {
  const list = collectIssues(issues)
  // Rien à afficher → ne rien rendre. On se base sur ce que l'APPELANT a fourni (un titre ou des
  // issues) ; le headline par défaut ci-dessous ne doit PAS forcer l'ouverture du bandeau au chargement
  // initial (title undefined + aucune issue) — sinon « Please check the required fields. » s'affiche à vide.
  if (!title && list.length === 0) return null
  const headline = title ?? 'Please check the required fields.'

  // Render a string as trusted HTML (opt-in) or as escaped text (default).
  const renderText = (value: string, s?: CSSProperties): ReactNode =>
    html
      ? <span style={s} dangerouslySetInnerHTML={{ __html: value }} />
      : <span style={s}>{value}</span>

  return (
    <div role="alert" style={{ ...box, ...style }}>
      <div style={{ display: 'flex', alignItems: 'flex-start', gap: 8 }}>
        {icon != null && <span style={{ flexShrink: 0, lineHeight: 1.4 }}>{icon}</span>}
        <div style={{ flex: 1, minWidth: 0 }}>
          {headline && <div style={{ fontWeight: 600 }}>{renderText(headline)}</div>}
          {list.length > 0 && (
            <ul style={listCss}>
              {list.map((it, i) => (
                <li key={i} style={{ fontSize: 13 }}>
                  {it.label && <span style={{ fontWeight: 600 }}>{it.label}{it.message ? ' — ' : ''}</span>}
                  {it.message && renderText(it.message)}
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </div>
  )
}
