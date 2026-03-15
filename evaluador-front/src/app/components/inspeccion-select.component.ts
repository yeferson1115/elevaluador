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
  @Input() selectorType = 'damaged';

  private readonly selectorOptions: Record<string, string[]> = {
    nivel: ['Normal', 'Medio', 'Bajo', 'No verificable', 'N/A'],
    estado: ['Excelente', 'Bueno', 'Regular', 'Malo', 'Deficiente', 'No verificable', 'N/A'],
    fugas: ['Sin fuga', 'Humedad', 'Fuga sin goteo', 'Fuga con goteo', 'Fuga continua', 'No verificable', 'N/A'],
    funcionamiento: ['Funciona', 'No funciona', 'No verificable', 'N/A'],
    damaged: ['Bueno', 'Leve', 'Medio', 'Fuerte', 'Abolladura', 'Rayón', 'Removido', 'N/A'],
    condición: ['Bueno', 'Regular', 'Malo', 'No verificable', 'N/A'],
    mecanica: ['Excelente', 'Bueno', 'Regular', 'Malo', 'Deficiente', 'No verificable', 'N/A'],
    llantas: ['Excelente', 'Bueno', 'Regular', 'Malo', 'Deficiente', 'No verificable', 'N/A']
  };

  get options(): string[] {
    return this.selectorOptions[this.selectorType.toLowerCase()] ?? this.selectorOptions['damaged'];
  }
}
