import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import * as XLSX from 'xlsx';
import { IngresoService } from '../../../core/services/Ingreso.service';
import { Ingreso } from '../../../core/interfaces/ingresos.interface';

interface RowInput {
  placa: string;
  chatarreria1: number;
  chatarreria2: number;
  chatarreria3: number;
  chatarreria4: number;
  factorSubasta: number;
}

interface RowResult extends RowInput {
  marca: string;
  clase: string;
  linea: string;
  modelo: string | number;
  cilindraje: string | number;
  carroceria: string;
  serie: string;
  chasis: string;
  vin: string;
  promedio: number;
  total: number;
}

@Component({
  selector: 'app-actualizacion-chatarra',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './actualizacion-chatarra.component.html'
})
export class ActualizacionChatarraComponent {
  loading = false;
  error = '';
  rows: RowResult[] = [];

  constructor(private readonly ingresoService: IngresoService) {}

  descargarFormatoExcel(): void {
    const data = [
      {
        placa: 'ABC123',
        chatarreria_1: 1200,
        chatarreria_2: 1100,
        chatarreria_3: 1300,
        chatarreria_4: 1250,
        factor_subasta: 0.85,
      }
    ];
    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'plantilla');
    XLSX.writeFile(wb, 'plantilla-actualizacion-chatarra.xlsx');
  }

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    this.loading = true;
    this.error = '';
    this.rows = [];

    const reader = new FileReader();
    reader.onload = () => {
      try {
        const workbook = XLSX.read(reader.result, { type: 'binary' });
        const sheetName = workbook.SheetNames[0];
        const sheet = workbook.Sheets[sheetName];
        const jsonData: any[] = XLSX.utils.sheet_to_json(sheet, { defval: '' });

        const parsedRows = jsonData
          .map((row) => this.parseRow(row))
          .filter((row): row is RowInput => !!row);

        this.resolveRows(parsedRows);
      } catch (e) {
        console.error(e);
        this.error = 'No fue posible leer el archivo. Verifica el formato.';
        this.loading = false;
      }
    };
    reader.readAsBinaryString(file);
  }

  private parseRow(row: any): RowInput | null {
    const placa = String(row.placa || row.PLACA || '').trim().toUpperCase();
    if (!placa) return null;

    const toNumber = (v: any) => Number(String(v).toString().replace(',', '.')) || 0;

    return {
      placa,
      chatarreria1: toNumber(row.chatarreria_1),
      chatarreria2: toNumber(row.chatarreria_2),
      chatarreria3: toNumber(row.chatarreria_3),
      chatarreria4: toNumber(row.chatarreria_4),
      factorSubasta: toNumber(row.factor_subasta),
    };
  }

  private resolveRows(inputs: RowInput[]): void {
    this.ingresoService.getAll().subscribe({
      next: (ingresos: Ingreso[]) => {
        const byPlaca = new Map(
          ingresos.map((ing) => [ing.datosGenerales?.placa?.toUpperCase(), ing])
        );

        this.rows = inputs.map((row) => {
          const ingreso = byPlaca.get(row.placa);
          const promedio =
            (row.chatarreria1 + row.chatarreria2 + row.chatarreria3 + row.chatarreria4) / 4;
          const total = promedio * row.factorSubasta;

          return {
            ...row,
            marca: ingreso?.informacionBien?.marca || 'N/A',
            clase: ingreso?.informacionBien?.clase || 'N/A',
            linea: ingreso?.informacionBien?.linea || 'N/A',
            modelo: ingreso?.informacionBien?.modelo || 'N/A',
            cilindraje: ingreso?.informacionBien?.cilindraje || 'N/A',
            carroceria: ingreso?.informacionBien?.tipoCarroceria || 'N/A',
            serie: ingreso?.informacionBien?.numeroSerie || 'N/A',
            chasis: ingreso?.informacionBien?.numeroChasis || 'N/A',
            vin: ingreso?.informacionBien?.numeroVin || 'N/A',
            promedio,
            total,
          };
        });

        this.loading = false;
      },
      error: () => {
        this.error = 'No fue posible consultar los datos de las placas.';
        this.loading = false;
      }
    });
  }

  generarPDF(): void {
    const printable = document.getElementById('printable-content');
    if (!printable || !this.rows.length) return;

    const w = window.open('', '_blank');
    if (!w) return;
    w.document.write(`
      <html><head><title>Actualización Chatarra</title>
      <style>
      body{font-family:Arial,sans-serif;padding:20px;} .page{page-break-after:always; margin-bottom: 24px;}
      table{width:100%;border-collapse:collapse;margin-top:8px;} td,th{border:1px solid #999;padding:4px;font-size:12px;}
      h3{margin:0 0 8px}
      </style>
      </head><body>${printable.innerHTML}</body></html>
    `);
    w.document.close();
    w.focus();
    w.print();
  }
}
