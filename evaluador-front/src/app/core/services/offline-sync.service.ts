import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, Subject, catchError, concatMap, from, map, of, tap } from 'rxjs';

interface PendingRequest {
  id: string;
  url: string;
  method: string;
  body: unknown;
  headers: Record<string, string>;
  queuedAt: string;
}

@Injectable({ providedIn: 'root' })
export class OfflineSyncService {
  private readonly storageKey = 'offline-http-queue';
  private readonly syncStatusSubject = new Subject<{ synced: number; failed: number }>();
  readonly syncStatus$ = this.syncStatusSubject.asObservable();

  constructor(private http: HttpClient) {
    window.addEventListener('online', () => {
      void this.syncPendingRequests().subscribe();
    });
  }

  queueRequest(request: Omit<PendingRequest, 'id' | 'queuedAt'>): void {
    const queue = this.getQueue();
    queue.push({
      ...request,
      id: crypto.randomUUID(),
      queuedAt: new Date().toISOString(),
    });
    this.saveQueue(queue);
  }

  getQueueLength(): number {
    return this.getQueue().length;
  }

  syncPendingRequests(): Observable<{ synced: number; failed: number }> {
    const queue = this.getQueue();

    if (!queue.length || !navigator.onLine) {
      return of({ synced: 0, failed: 0 });
    }

    let synced = 0;
    let failed = 0;

    return from(queue).pipe(
      concatMap((request) =>
        this.http.request(request.method, request.url, {
          body: request.body,
          headers: new HttpHeaders(request.headers),
        }).pipe(
          tap(() => {
            synced += 1;
            this.removeRequestFromQueue(request.id);
          }),
          catchError(() => {
            failed += 1;
            return of(null);
          }),
        ),
      ),
      map(() => ({ synced, failed })),
      tap((result) => this.syncStatusSubject.next(result)),
      catchError(() => of({ synced, failed: queue.length })),
    );
  }

  private getQueue(): PendingRequest[] {
    const raw = localStorage.getItem(this.storageKey);
    if (!raw) {
      return [];
    }

    try {
      return JSON.parse(raw) as PendingRequest[];
    } catch {
      return [];
    }
  }

  private saveQueue(queue: PendingRequest[]): void {
    localStorage.setItem(this.storageKey, JSON.stringify(queue));
  }

  private removeRequestFromQueue(id: string): void {
    const queue = this.getQueue().filter((request) => request.id !== id);
    this.saveQueue(queue);
  }
}
