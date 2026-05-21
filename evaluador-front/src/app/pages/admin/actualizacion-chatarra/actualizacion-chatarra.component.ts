import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import * as XLSX from 'xlsx';
import { IngresoService } from '../../../core/services/Ingreso.service';
import { Ingreso } from '../../../core/interfaces/ingresos.interface';
import { forkJoin, of } from 'rxjs';
import { catchError, map } from 'rxjs/operators';

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
  evaluador: string;
  pesoChatarraKg: number | string;
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
            evaluador: (ingreso as any)?.avaluo?.evaluador || 'N/A',
            pesoChatarraKg: (ingreso as any)?.avaluo?.peso_chatarra_kg ?? '-',
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

  async generarZipPDFs(): Promise<void> {
    if (!this.rows.length || this.downloadingZip) return;

    this.downloadingZip = true;
    this.error = '';

    try {
      this.ingresoService.exportActualizacionChatarraZip(this.rows).subscribe({
        next: (zipBlob) => {
          this.downloadBlob(zipBlob, `actualizacion-chatarra-${new Date().toISOString().slice(0, 10)}.zip`);
          this.downloadingZip = false;
        },
        error: (error) => {
          console.error(error);
          this.error = 'No fue posible generar el ZIP del formato de actualización de chatarra.';
          this.downloadingZip = false;
        }
      });
    } catch (error) {
      console.error(error);
      this.error = 'No fue posible generar el ZIP del formato de actualización de chatarra.';
      this.downloadingZip = false;
    }
  }

  private async createZipFromFiles(files: Array<{ name: string; content: string }>): Promise<Blob> {
    const encoder = new TextEncoder();
    const localParts: Uint8Array[] = [];
    const centralParts: Uint8Array[] = [];
    let offset = 0;

    for (const file of files) {
      const nameBytes = encoder.encode(file.name);
      const data = encoder.encode(file.content);
      const crc = this.crc32(data);

      const localHeader = new Uint8Array(30 + nameBytes.length);
      const localView = new DataView(localHeader.buffer);
      localView.setUint32(0, 0x04034b50, true);
      localView.setUint16(4, 20, true);
      localView.setUint16(6, 0, true);
      localView.setUint16(8, 0, true);
      localView.setUint16(10, 0, true);
      localView.setUint16(12, 0, true);
      localView.setUint32(14, crc, true);
      localView.setUint32(18, data.length, true);
      localView.setUint32(22, data.length, true);
      localView.setUint16(26, nameBytes.length, true);
      localView.setUint16(28, 0, true);
      localHeader.set(nameBytes, 30);

      localParts.push(localHeader, data);

      const centralHeader = new Uint8Array(46 + nameBytes.length);
      const centralView = new DataView(centralHeader.buffer);
      centralView.setUint32(0, 0x02014b50, true);
      centralView.setUint16(4, 20, true);
      centralView.setUint16(6, 20, true);
      centralView.setUint16(8, 0, true);
      centralView.setUint16(10, 0, true);
      centralView.setUint16(12, 0, true);
      centralView.setUint16(14, 0, true);
      centralView.setUint32(16, crc, true);
      centralView.setUint32(20, data.length, true);
      centralView.setUint32(24, data.length, true);
      centralView.setUint16(28, nameBytes.length, true);
      centralView.setUint16(30, 0, true);
      centralView.setUint16(32, 0, true);
      centralView.setUint16(34, 0, true);
      centralView.setUint16(36, 0, true);
      centralView.setUint32(38, 0, true);
      centralView.setUint32(42, offset, true);
      centralHeader.set(nameBytes, 46);
      centralParts.push(centralHeader);

      offset += localHeader.length + data.length;
    }

    const centralSize = centralParts.reduce((sum, part) => sum + part.length, 0);
    const end = new Uint8Array(22);
    const endView = new DataView(end.buffer);
    endView.setUint32(0, 0x06054b50, true);
    endView.setUint16(4, 0, true);
    endView.setUint16(6, 0, true);
    endView.setUint16(8, files.length, true);
    endView.setUint16(10, files.length, true);
    endView.setUint32(12, centralSize, true);
    endView.setUint32(16, offset, true);
    endView.setUint16(20, 0, true);

    return new Blob([...localParts, ...centralParts, end], { type: 'application/zip' });
  }

  private crc32(data: Uint8Array): number {
    let crc = 0 ^ -1;
    for (let i = 0; i < data.length; i++) {
      crc = (crc >>> 8) ^ this.crcTable[(crc ^ data[i]) & 0xff];
    }
    return (crc ^ -1) >>> 0;
  }

  private readonly crcTable: Uint32Array = (() => {
    const table = new Uint32Array(256);
    for (let n = 0; n < 256; n++) {
      let c = n;
      for (let k = 0; k < 8; k++) {
        c = (c & 1) ? (0xedb88320 ^ (c >>> 1)) : (c >>> 1);
      }
      table[n] = c >>> 0;
    }
    return table;
  })();

  private downloadBlob(blob: Blob, filename: string): void {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    URL.revokeObjectURL(url);
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
