export const PATRIMONIO_ROUTES = [
  '/patrimonio/principal',
  '/patrimonio/bens',
  '/patrimonio/conferencia',
] as const;

export type PatrimonioRoute = (typeof PATRIMONIO_ROUTES)[number];
export type PatrimonioNavigationDirection = 'forward' | 'backward';

let patrimonioNavigationDirection: PatrimonioNavigationDirection = 'forward';

export function getPatrimonioRouteIndex(pathname: string): number {
  return PATRIMONIO_ROUTES.findIndex((route) => route === pathname);
}

export function setPatrimonioNavigationDirection(direction: PatrimonioNavigationDirection): void {
  patrimonioNavigationDirection = direction;
}

export function setPatrimonioNavigationDirectionFromRoutes(
  currentPathname: string,
  nextPathname: PatrimonioRoute,
): void {
  const currentIndex = getPatrimonioRouteIndex(currentPathname);
  const nextIndex = getPatrimonioRouteIndex(nextPathname);

  if (currentIndex === -1 || nextIndex === -1) {
    setPatrimonioNavigationDirection('forward');
    return;
  }

  setPatrimonioNavigationDirection(nextIndex > currentIndex ? 'forward' : 'backward');
}

export function getPatrimonioStackAnimation(): 'slide_from_right' | 'slide_from_left' {
  return patrimonioNavigationDirection === 'forward'
    ? 'slide_from_right'
    : 'slide_from_left';
}
