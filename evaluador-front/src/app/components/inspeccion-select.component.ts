import { Component, Input, Optional, SkipSelf } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, ControlContainer } from '@angular/forms';

@Component({
  selector: 'app-inspeccion-select',
  standalone: true,
  templateUrl: './inspeccion-select.component.html',
  imports: [CommonModule, ReactiveFormsModule],
  viewProviders: [
    {
      provide: ControlContainer,
      useFactory: (parent: ControlContainer) => parent,
      deps: [[new SkipSelf(), new Optional(), ControlContainer]],
    },
  ],
})
export class InspeccionSelectComponent {
  @Input() label!: string;
  @Input() formControlName!: string;
}
