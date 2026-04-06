import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { IngresoService } from '../../../core/services/Ingreso.service';
import { AlertService } from '../../../core/services/alert.service';
import { GetImagenesResponse, ImagenResponse } from '../../../core/models/ingresoimagenes.model';

interface ImagenItem {
  id: number;
  url: string;
  orden: number;
  rotacion: number;
}

interface Categoria {
  key: string;
  nombre: string;
  max: number;
  imagenes: ImagenItem[];
  isDragging: boolean;
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

  constructor(
    private route: ActivatedRoute,
    private service: IngresoService,
    private alertService: AlertService
  ) {
    this.id = Number(this.route.snapshot.paramMap.get('id'));
  }

  ngOnInit(): void {
    this.isMobileDevice = this.detectarDispositivoMovil();
    this.cargarImagenes();
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

      },
      error: (err) => this.alertService.error('Error cargando imágenes', err.message)
    });
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

    this.subirArchivos(imageFiles, categoria);
  }

  subirArchivos(files: File[], categoria: Categoria) {
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

    this.subirArchivos(imageFiles, categoria);
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
    this.sincronizarOrden(categoria, true);
  }

  rotarImagen(categoria: Categoria, imagen: ImagenItem, grados: number) {
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
    });

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
}
