export type AppErrorKind = 'network' | 'server' | 'unexpected';

export interface AppErrorEvent {
  kind: AppErrorKind;
  title: string;
  message: string;
  status?: number;
}

type AppErrorListener = (event: AppErrorEvent) => void;

const listeners = new Set<AppErrorListener>();

export function subscribeAppError(listener: AppErrorListener): () => void {
  listeners.add(listener);

  return () => {
    listeners.delete(listener);
  };
}

export function notifyAppError(event: AppErrorEvent): void {
  listeners.forEach((listener) => listener(event));
}
