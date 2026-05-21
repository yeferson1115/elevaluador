import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import * as XLSX from 'xlsx';
import { IngresoService } from '../../../core/services/Ingreso.service';
import { Ingreso } from '../../../core/interfaces/ingresos.interface';
import { forkJoin, of } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import { saveAs } from 'file-saver';

interface RowInput {
  placa: string;
  nombreChatarreria1: string;
  chatarreria1: number;
  nombreChatarreria2: string;
  chatarreria2: number;
  nombreChatarreria3: string;
  chatarreria3: number;
  nombreChatarreria4: string;
  chatarreria4: number;
  factorSubasta: number;
}

interface RowResult extends RowInput {
  ingresoId?: number;
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
  downloadingZip = false;
  error = '';
  rows: RowResult[] = [];

  constructor(private readonly ingresoService: IngresoService) {}

  descargarFormatoExcel(): void {
    const data = [
      {
        placa: 'ABC123',
        nombre_chatarreria_1: 'CHATARRERIA LA 66',
        chatarreria_1: 1200,
        nombre_chatarreria_2: 'CHATARRERIA A TOLIMA',
        chatarreria_2: 1100,
        nombre_chatarreria_3: 'CHATARRERIA SOACHA',
        chatarreria_3: 1300,
        nombre_chatarreria_4: 'CHATARRERIA SBS BOGOTA',
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
      nombreChatarreria1: String(row.nombre_chatarreria_1 || 'CHATARRERIA 1').trim(),
      chatarreria1: toNumber(row.chatarreria_1),
      nombreChatarreria2: String(row.nombre_chatarreria_2 || 'CHATARRERIA 2').trim(),
      chatarreria2: toNumber(row.chatarreria_2),
      nombreChatarreria3: String(row.nombre_chatarreria_3 || 'CHATARRERIA 3').trim(),
      chatarreria3: toNumber(row.chatarreria_3),
      nombreChatarreria4: String(row.nombre_chatarreria_4 || 'CHATARRERIA 4').trim(),
      chatarreria4: toNumber(row.chatarreria_4),
      factorSubasta: toNumber(row.factor_subasta),
    };
  }

  private resolveRows(inputs: RowInput[]): void {
    const placas = Array.from(new Set(inputs.map((x) => x.placa)));

    const requests = placas.map((placa) =>
      this.ingresoService.getAvaluos(1, placa).pipe(
        map((res) => {
          const match = (res?.data || []).find((ing: Ingreso) =>
            (ing?.datosGenerales?.placa || '').toUpperCase() === placa
          );
          return [placa, match || null] as const;
        }),
        catchError(() => of([placa, null] as const))
      )
    );

    forkJoin(requests).subscribe({
      next: (pairs) => {
        const byPlaca = new Map<string, Ingreso | null>(pairs);

        this.rows = inputs.map((row) => {
          const ingreso = byPlaca.get(row.placa);
          const promedio =
            (row.chatarreria1 + row.chatarreria2 + row.chatarreria3 + row.chatarreria4) / 4;
          const total = promedio * row.factorSubasta;

          return {
            ...row,
            ingresoId: ingreso?.id,
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

  generarZipPDFs(): void {
    if (!this.rows.length || this.downloadingZip) return;

    const ids = Array.from(new Set(this.rows.map((r) => r.ingresoId).filter((id): id is number => !!id)));

    if (!ids.length) {
      this.error = 'No se encontraron IDs válidos para exportar ZIP. Verifica que las placas existan en el sistema.';
      return;
    }

    this.downloadingZip = true;
    this.error = '';

    this.ingresoService.exportCertificadosZip('', ids).subscribe({
      next: (zipBlob: Blob) => {
        const nombre = `actualizacion-chatarra-${new Date().toISOString().slice(0, 10)}.zip`;
        saveAs(zipBlob, nombre);
        this.downloadingZip = false;
      },
      error: () => {
        this.error = 'No fue posible generar el ZIP. Intenta nuevamente o valida permisos del endpoint de exportación.';
        this.downloadingZip = false;
      },
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
