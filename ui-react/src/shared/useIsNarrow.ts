import { useEffect, useState } from 'react'

/**
 * True when the viewport is narrower than `breakpoint`. Drives every responsive decision on
 * this brick as a JS ternary (inline styles) instead of a CSS media query — see the
 * `melis-react-mobile-responsive` skill for why.
 */
export function useIsNarrow(breakpoint = 640): boolean {
  const [narrow, setNarrow] = useState(() => window.innerWidth < breakpoint)
  useEffect(() => {
    const onResize = () => setNarrow(window.innerWidth < breakpoint)
    window.addEventListener('resize', onResize)
    return () => window.removeEventListener('resize', onResize)
  }, [breakpoint])
  return narrow
}

/**
 * Largeur courante du viewport. `useIsNarrow` ne re-rend que quand le booléen BASCULE — pour une
 * décision qui dépend des pixels disponibles (ex. un libellé tient-il dans un demi-bouton ?), il
 * faut la largeur elle-même, réactive à chaque redimensionnement.
 */
export function useViewportWidth(): number {
  const [w, setW] = useState(() => window.innerWidth)
  useEffect(() => {
    const onResize = () => setW(window.innerWidth)
    window.addEventListener('resize', onResize)
    return () => window.removeEventListener('resize', onResize)
  }, [])
  return w
}
