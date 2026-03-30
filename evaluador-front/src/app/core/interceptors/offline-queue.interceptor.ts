import { HttpEvent, HttpHandlerFn, HttpInterceptorFn, HttpRequest, HttpResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { Observable, of } from 'rxjs';
import { OfflineSyncService } from '../services/offline-sync.service';

function isSerializableBody(body: unknown): boolean {
  return !(body instanceof FormData || body instanceof Blob || body instanceof ArrayBuffer);
}

function serializeHeaders(headers: HttpRequest<unknown>['headers']): Record<string, string> {
  return headers.keys().reduce<Record<string, string>>((acc, key) => {
    const value = headers.get(key);
    if (value) {
      acc[key] = value;
    }
    return acc;
  }, {});
}

export const offlineQueueInterceptor: HttpInterceptorFn = (
  req: HttpRequest<unknown>,
  next: HttpHandlerFn,
): Observable<HttpEvent<unknown>> => {
  const offlineSyncService = inject(OfflineSyncService);
  const mutationMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

  if (!navigator.onLine && mutationMethods.includes(req.method.toUpperCase())) {
    if (!isSerializableBody(req.body)) {
      return of(
        new HttpResponse({
          status: 503,
          statusText: 'Offline request body not supported for queueing',
          body: { offline: true, queued: false },
        }),
      );
    }

    offlineSyncService.queueRequest({
      url: req.url,
      method: req.method,
      body: req.body,
      headers: serializeHeaders(req.headers),
    });

    return of(
      new HttpResponse({
        status: 202,
        statusText: 'Request queued for sync',
        body: { offline: true, queued: true },
      }),
    );
  }

  return next(req);
};
