import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { IngresoService } from '../../../core/services/Ingreso.service';
import { AlertService } from '../../../core/services/alert.service';
import { GetImagenesResponse, ImagenResponse } from '../../../core/models/ingresoimagenes.model';
import { firstValueFrom } from 'rxjs';

interface ImagenItem {
  id: number;
  url: string;
  orden: number;
  rotacion: number;
  pending?: boolean;
  localId?: string;
}

interface Categoria {
  key: string;
  nombre: string;
  max: number;
  imagenes: ImagenItem[];
  isDragging: boolean;
}

interface PendingImageUpload {
  localId: string;
  avaluoId: number;
  categoria: string;
  filename: string;
  mimeType: string;
  dataUrl: string;
  createdAt: string;
}

declare var bootstrap: any;

@Component({
  selector: 'app-ingreso-imagenes',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './ingreso-imagenes.component.html',
  styleUrls: ['./ingreso-imagenes.component.css']
})
export class IngresoImagenesComponent implements OnInit {
  id!: number;
  infoIngreso: any = null;
  isMobileDevice = false;
  private readonly categoriasBase: Categoria[] = [
    { key: 'matricula', nombre: 'Foto Matricula', max: 1, imagenes: [], isDragging: false },
    { key: 'firma_evaluador', nombre: 'Firma Evaluador', max: 1, imagenes: [], isDragging: false },
    { key: 'firma_inspector', nombre: 'Firma Inspector', max: 1, imagenes: [], isDragging: false },
    { key: 'frontal', nombre: 'Foto frontal', max: 1, imagenes: [], isDragging: false },
    { key: 'izquierda', nombre: 'Foto izquierda', max: 1, imagenes: [], isDragging: false },
    { key: 'trasera', nombre: 'Foto trasera', max: 1, imagenes: [], isDragging: false },
    { key: 'derecha', nombre: 'Foto derecha', max: 1, imagenes: [], isDragging: false },
    { key: 'motor', nombre: 'Foto motor', max: 2, imagenes: [], isDragging: false },
    { key: 'sistema_identificacion', nombre: 'Foto sistema identificación', max: 2, imagenes: [], isDragging: false },
    { key: 'habitaculo', nombre: 'Foto habitáculo', max: 2, imagenes: [], isDragging: false },
    { key: 'baul', nombre: 'Foto baúl', max: 2, imagenes: [], isDragging: false },
    { key: 'llanta_delantera_der', nombre: 'Foto llanta delantera derecha', max: 1, imagenes: [], isDragging: false },
    { key: 'llanta_delantera_izq', nombre: 'Foto llanta delantera izquierda', max: 1, imagenes: [], isDragging: false },
    { key: 'llantas_trasera_der', nombre: 'Foto llanta trasera derecha', max: 1, imagenes: [], isDragging: false },
    { key: 'llantas_trasera_izq', nombre: 'Foto llanta trasera izquierda', max: 1, imagenes: [], isDragging: false },
    { key: 'parte_baja', nombre: 'Foto parte baja', max: 3, imagenes: [], isDragging: false },
    { key: 'extra', nombre: 'Foto extra', max: 10, imagenes: [], isDragging: false },
  ];
  categorias: Categoria[] = this.crearCategorias(this.categoriasBase);
  imagenSeleccionada: string | null = null;
  placaVisible = '';
  private isSyncingOfflineImages = false;

  constructor(
    private route: ActivatedRoute,
    private service: IngresoService,
    private alertService: AlertService
  ) {
    this.id = Number(this.route.snapshot.paramMap.get('id'));
  }

  ngOnInit(): void {
    this.isMobileDevice = this.detectarDispositivoMovil();
    window.addEventListener('online', () => {
      void this.sincronizarImagenesPendientes();
    });
    this.cargarImagenes();
    void this.sincronizarImagenesPendientes();
  }

  private detectarDispositivoMovil(): boolean {
    if (typeof navigator === 'undefined') {
      return false;
    }

    const userAgent = navigator.userAgent.toLowerCase();
    const mobileRegex = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i;
    const isTouchDevice = (typeof window !== 'undefined' && 'ontouchstart' in window) || navigator.maxTouchPoints > 0;

    return mobileRegex.test(userAgent) && isTouchDevice;
  }

  cargarImagenes() {
    this.service.getImagenes(this.id).subscribe({
      next: (response: GetImagenesResponse) => {
        this.infoIngreso = response.ingreso;
        this.placaVisible = this.obtenerPlacaVisible(response.ingreso);
        this.configurarCategorias();

        this.categorias.forEach(cat => cat.imagenes = []);

        for (const img of response.imagenes) {
          const cat = this.categorias.find(c => c.key === img.categoria);
          if (cat && cat.imagenes.length < cat.max) {
            cat.imagenes.push({
              id: img.id,
              url: img.url,
              orden: img.orden ?? cat.imagenes.length + 1,
              rotacion: img.rotacion ?? 0,
            });
          }
        }

        this.categorias.forEach(cat => {
          cat.imagenes.sort((a, b) => a.orden - b.orden || a.id - b.id);
        });

        this.pintarImagenesPendientesLocales();

      },
      error: (err) => this.alertService.error('Error cargando imágenes', err.message)
    });
  }

  private obtenerPlacaVisible(ingreso: any): string {
    return (
      ingreso?.placa ||
      ingreso?.datosGenerales?.placa ||
      ingreso?.datos_generales?.placa ||
      'SIN PLACA'
    );
  }

  private configurarCategorias(): void {
    const categoriasVisibles = this.esAvaluoCompact()
      ? this.categoriasBase.filter((cat) => cat.key === 'extra')
      : this.categoriasBase;

    this.categorias = this.crearCategorias(categoriasVisibles);
  }

  private esAvaluoCompact(): boolean {
    return this.infoIngreso?.tiposervicio === 'Sec Bogota';
  }

  private crearCategorias(categorias: Categoria[]): Categoria[] {
    return categorias.map((cat) => ({
      ...cat,
      imagenes: [],
      isDragging: false,
    }));
  }

  onDragOver(event: DragEvent, categoria: Categoria) {
    event.preventDefault();
    event.stopPropagation();
    if (this.puedeSubirMasImagenes(categoria)) {
      categoria.isDragging = true;
    }
  }

  onDragLeave(event: DragEvent, categoria: Categoria) {
    event.preventDefault();
    event.stopPropagation();
    categoria.isDragging = false;
  }

  onDrop(event: DragEvent, categoria: Categoria) {
    event.preventDefault();
    event.stopPropagation();
    categoria.isDragging = false;

    if (!this.puedeSubirMasImagenes(categoria)) {
      this.alertService.warning('Límite alcanzado', `Esta categoría solo permite ${categoria.max} imagen(es).`);
      return;
    }

    const files = event.dataTransfer?.files;
    if (!files || files.length === 0) return;

    const imageFiles: File[] = [];
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      if (file.type.startsWith('image/')) {
        imageFiles.push(file);
      }
    }

    if (imageFiles.length === 0) {
      this.alertService.warning('Archivos no válidos', 'Solo se permiten imágenes.');
      return;
    }

    const totalDespuesDeSubir = categoria.imagenes.length + imageFiles.length;
    if (totalDespuesDeSubir > categoria.max) {
      const disponibles = categoria.max - categoria.imagenes.length;
      this.alertService.warning('Demasiadas imágenes', `Solo puedes subir ${disponibles} imagen(es) más para esta categoría.`);
      return;
    }

    void this.subirArchivos(imageFiles, categoria);
  }

  async subirArchivos(files: File[], categoria: Categoria): Promise<void> {
    if (!navigator.onLine) {
      await this.encolarImagenesOffline(files, categoria);
      return;
    }

    const formData = new FormData();

    files.forEach(file => {
      formData.append('imagenes[]', file);
    });

    formData.append('categoria', categoria.key);

    this.service.uploadImagen(this.id, formData).subscribe({
      next: (res: { imagenes: ImagenResponse[] }) => {
        const nuevas = (res.imagenes || []).map((img, idx) => ({
          id: img.id,
          url: img.url,
          orden: img.orden ?? categoria.imagenes.length + idx + 1,
          rotacion: img.rotacion ?? 0,
        }));

        categoria.imagenes = [...categoria.imagenes, ...nuevas]
          .sort((a, b) => a.orden - b.orden || a.id - b.id)
          .slice(0, categoria.max);

        this.alertService.success('Imágenes subidas correctamente');
      },
      error: (err) => {
        this.alertService.error('Error al subir las imágenes', err.message || '');
      }
    });
  }

  onFileChange(event: any, categoria: Categoria) {
    const files = event.target.files;
    if (!files || files.length === 0) return;

    const imageFiles: File[] = [];
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      if (file.type.startsWith('image/')) {
        imageFiles.push(file);
      }
    }

    if (imageFiles.length === 0) {
      this.alertService.warning('Archivos no válidos', 'Solo se permiten imágenes.');
      return;
    }

    const totalDespuesDeSubir = categoria.imagenes.length + imageFiles.length;
    if (totalDespuesDeSubir > categoria.max) {
      const disponibles = categoria.max - categoria.imagenes.length;
      this.alertService.warning('Demasiadas imágenes', `Solo puedes subir ${disponibles} imagen(es) más para esta categoría.`);
      return;
    }

    void this.subirArchivos(imageFiles, categoria);
    event.target.value = '';
  }

  eliminarImagen(categoria: Categoria, imagen: ImagenItem) {
    this.alertService.confirm({
      title: '¿Eliminar imagen?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        if (imagen.pending && imagen.localId) {
          this.eliminarImagenPendiente(categoria, imagen.localId);
          return;
        }

        this.service.deleteImagen(this.id, categoria.key, imagen.url).subscribe({
          next: () => {
            categoria.imagenes = categoria.imagenes.filter(i => i.id !== imagen.id);
            this.sincronizarOrden(categoria, false);
            this.alertService.success('Imagen eliminada');
          },
          error: () => this.alertService.error('No se pudo eliminar la imagen')
        });
      }
    });
  }

  moverImagen(categoria: Categoria, index: number, direction: 'up' | 'down') {
    const target = direction === 'up' ? index - 1 : index + 1;
    if (target < 0 || target >= categoria.imagenes.length) return;

    [categoria.imagenes[index], categoria.imagenes[target]] = [categoria.imagenes[target], categoria.imagenes[index]];
    if (categoria.imagenes.some((img) => img.pending)) {
      categoria.imagenes.forEach((img, idx) => (img.orden = idx + 1));
      this.alertService.info('El orden de imágenes pendientes se sincronizará cuando tengas internet.');
      return;
    }

    this.sincronizarOrden(categoria, true);
  }

  rotarImagen(categoria: Categoria, imagen: ImagenItem, grados: number) {
    if (imagen.pending) {
      this.alertService.warning('Esta imagen está pendiente de sincronización. Rótala cuando esté subida.');
      return;
    }

    this.service.rotarImagen(this.id, categoria.key, imagen.url, grados).subscribe({
      next: (res) => {
        imagen.url = res.url || imagen.url;
        imagen.rotacion = res.rotacion ?? ((imagen.rotacion + grados + 360) % 360);
        this.alertService.success('Imagen rotada correctamente');
      },
      error: (err) => {
        this.alertService.error('No se pudo rotar la imagen', err?.message || '');
      }
    });
  }

  abrirModal(img: string) {
    this.imagenSeleccionada = img;
    const modal = new bootstrap.Modal(document.getElementById('modalImagen')!);
    modal.show();
  }

  private sincronizarOrden(categoria: Categoria, mostrarMensajeExito = false) {
    const orden = categoria.imagenes.map((img, index) => {
      img.orden = index + 1;
      return img.id;
    }).filter((id) => id > 0);

    if (!orden.length) {
      return;
    }

    this.service.reordenarImagenes(this.id, categoria.key, orden).subscribe({
      next: () => {
        if (mostrarMensajeExito) {
          this.alertService.success('Orden actualizado. Este orden se respeta en el PDF.');
        }
      },
      error: (err) => {
        this.alertService.error('No se pudo actualizar el orden', err?.message || '');
      }
    });
  }

  private puedeSubirMasImagenes(categoria: Categoria): boolean {
    return categoria.imagenes.length < categoria.max;
  }

  getEmptySlots(categoria: Categoria): number[] {
    const emptySlots = categoria.max - categoria.imagenes.length;
    return emptySlots > 0 ? Array(emptySlots).fill(0) : [];
  }

  private get storageKey(): string {
    return `offline-image-queue-${this.id}`;
  }

  private getPendingQueue(): PendingImageUpload[] {
    const raw = localStorage.getItem(this.storageKey);
    if (!raw) return [];
    try {
      return JSON.parse(raw) as PendingImageUpload[];
    } catch {
      return [];
    }
  }

  private savePendingQueue(queue: PendingImageUpload[]): void {
    localStorage.setItem(this.storageKey, JSON.stringify(queue));
  }

  private async encolarImagenesOffline(files: File[], categoria: Categoria): Promise<void> {
    const queue = this.getPendingQueue();
    const baseOrder = categoria.imagenes.length;

    const nuevosPendientes = await Promise.all(
      files.map(async (file, index) => {
        const dataUrl = await this.fileToDataUrl(file);
        const localId = `${Date.now()}-${index}-${Math.random().toString(36).slice(2, 8)}`;
        return {
          queueItem: {
            localId,
            avaluoId: this.id,
            categoria: categoria.key,
            filename: file.name,
            mimeType: file.type || 'image/jpeg',
            dataUrl,
            createdAt: new Date().toISOString(),
          } as PendingImageUpload,
          imageItem: {
            id: -(Date.now() + index),
            url: dataUrl,
            orden: baseOrder + index + 1,
            rotacion: 0,
            pending: true,
            localId,
          } as ImagenItem,
        };
      }),
    );

    queue.push(...nuevosPendientes.map((item) => item.queueItem));
    this.savePendingQueue(queue);

    categoria.imagenes = [...categoria.imagenes, ...nuevosPendientes.map((item) => item.imageItem)]
      .sort((a, b) => a.orden - b.orden || a.id - b.id)
      .slice(0, categoria.max);

    this.alertService.success('Foto guardada sin internet. Se sincronizará automáticamente cuando vuelvas a estar en línea.');
  }

  private pintarImagenesPendientesLocales(): void {
    const queue = this.getPendingQueue();
    if (!queue.length) return;

    queue.forEach((item) => {
      const categoria = this.categorias.find((cat) => cat.key === item.categoria);
      if (!categoria || categoria.imagenes.length >= categoria.max) return;
      categoria.imagenes.push({
        id: -Math.floor(Math.random() * 1_000_000_000),
        url: item.dataUrl,
        orden: categoria.imagenes.length + 1,
        rotacion: 0,
        pending: true,
        localId: item.localId,
      });
    });
  }

  private async sincronizarImagenesPendientes(): Promise<void> {
    if (!navigator.onLine || this.isSyncingOfflineImages) {
      return;
    }

    const queue = this.getPendingQueue();
    if (!queue.length) {
      return;
    }

    this.isSyncingOfflineImages = true;
    let synced = 0;
    const failed: PendingImageUpload[] = [];

    for (const pending of queue) {
      try {
        const file = this.dataUrlToFile(pending.dataUrl, pending.filename, pending.mimeType);
        const formData = new FormData();
        formData.append('imagenes[]', file);
        formData.append('categoria', pending.categoria);

        await firstValueFrom(this.service.uploadImagen(this.id, formData));
        synced += 1;
      } catch {
        failed.push(pending);
      }
    }

    this.savePendingQueue(failed);
    this.isSyncingOfflineImages = false;

    if (synced > 0) {
      this.alertService.success(`Se sincronizaron ${synced} imagen(es) pendientes.`);
      this.cargarImagenes();
    }

    if (failed.length > 0) {
      this.alertService.warning(`Quedaron ${failed.length} imagen(es) pendientes por sincronizar.`);
    }
  }

  private eliminarImagenPendiente(categoria: Categoria, localId: string): void {
    categoria.imagenes = categoria.imagenes.filter((img) => img.localId !== localId);
    const queue = this.getPendingQueue().filter((item) => item.localId !== localId);
    this.savePendingQueue(queue);
    this.alertService.success('Imagen pendiente eliminada');
  }

  private fileToDataUrl(file: File): Promise<string> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result as string);
      reader.onerror = () => reject(new Error('No se pudo leer la imagen'));
      reader.readAsDataURL(file);
    });
  }

  private dataUrlToFile(dataUrl: string, filename: string, mimeType: string): File {
    const base64 = dataUrl.split(',')[1] || '';
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }
    return new File([bytes], filename, { type: mimeType });
  }
}
