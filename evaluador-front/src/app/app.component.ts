import { Component, inject } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { LoaderComponent } from './core/components/loader/loader.component';
import { OfflineSyncService } from './core/services/offline-sync.service';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet,LoaderComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css'
})
export class AppComponent {
  title = 'homeprop-front';
  private readonly offlineSyncService = inject(OfflineSyncService);

  constructor() {
    void this.offlineSyncService.syncPendingRequests().subscribe();
  }
}
