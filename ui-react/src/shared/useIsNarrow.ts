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
