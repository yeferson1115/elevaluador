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
    damaged: ['Golpe', 'Abolladura', 'Rayón', 'Leve', 'Medio', 'Fuerte', 'Removido', 'Bueno', 'N/A'],
    condicion: ['Bueno', 'Regular', 'Malo', 'No verificable', 'N/A'],
    mecanica: ['Excelente', 'Bueno', 'Regular', 'Malo', 'Deficiente', 'No verificable', 'N/A'],
    llantas: ['Excelente', 'Bueno', 'Regular', 'Malo', 'Deficiente', 'No verificable', 'N/A']
  };

  private normalizeSelectorType(value: string | null | undefined): string {
    return (value ?? 'damaged')
      .toString()
      .trim()
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }

  get options(): string[] {
    const key = this.normalizeSelectorType(this.selectorType);
    const selectedOptions = this.selectorOptions[key];

    if (Array.isArray(selectedOptions) && selectedOptions.length > 0) {
      return selectedOptions;
    }

    return this.selectorOptions['damaged'];
  }
}
