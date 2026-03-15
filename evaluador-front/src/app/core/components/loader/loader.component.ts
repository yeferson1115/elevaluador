import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LoaderService } from '../../services/loader.service';

@Component({
  selector: 'app-loader',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div *ngIf="loaderService.loading$ | async" class="loader-overlay">
      <div class="spinner"></div>
    </div>
  `,
  styleUrls: ['./loader.component.css']
})
export class LoaderComponent {
  constructor(public loaderService: LoaderService) {}
}
