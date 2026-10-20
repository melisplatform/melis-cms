import { useEffect, useState } from 'react'

/**
 * Flag of a CMS language — the images the legacy CMS tools use (`/MelisCms/images/lang-flags/<locale>.png`,
 * e.g. es_ES, de_DE, pt_PT…; MelisCore's short-code set only had en/fr/it/pl/ro). When a locale has
 * no image, its 2-letter code is shown instead, so a language never disappears from a list.
 */
export function LangFlag({ locale, name, size = 18, radius = 2 }: { locale?: string; name?: string; size?: number; radius?: number }) {
  const [failed, setFailed] = useState(false)
  useEffect(() => { setFailed(false) }, [locale])
  if (!locale || !/^[a-zA-Z]{2}/.test(locale)) return null
  const h = Math.round(size * 0.7)
  if (failed) {
    return (
      <span title={name} style={{
        display: 'inline-flex', alignItems: 'center', justifyContent: 'center', minWidth: size, height: h,
        padding: '0 2px', borderRadius: radius, fontSize: Math.max(8, h - 3), fontWeight: 700, lineHeight: 1,
        background: 'var(--color-muted, #e5e7eb)', color: 'var(--color-muted-foreground, #6b7280)',
        flexShrink: 0, verticalAlign: 'middle',
      }}>{locale.slice(0, 2).toUpperCase()}</span>
    )
  }
  return (
    <img src={`/MelisCms/images/lang-flags/${locale}.png`} alt={name ?? ''} title={name} width={size} height={h}
      style={{ display: 'inline-block', borderRadius: radius, objectFit: 'cover', boxShadow: '0 0 0 1px rgba(0,0,0,.1)', flexShrink: 0, verticalAlign: 'middle' }}
      onError={() => setFailed(true)} />
  )
}
