import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { IngresoService } from '../../../core/services/Ingreso.service';
import { AlertService } from '../../../core/services/alert.service'; 
import { GetImagenesResponse } from '../../../core/models/ingresoimagenes.model';

interface Categoria {
  key: string;
  nombre: string;
  max: number;
  imagenes: string[];
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
  categorias: Categoria[] = [
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
  imagenSeleccionada: string | null = null;

  constructor(
    private route: ActivatedRoute,
    private service: IngresoService,
    private alertService: AlertService
  ) {
    this.id = Number(this.route.snapshot.paramMap.get('id'));
  }

  ngOnInit(): void {
    this.cargarImagenes();
  }

  // En el componente
cargarImagenes() {
  this.service.getImagenes(this.id).subscribe({
    next: (response: GetImagenesResponse) => { // Tipar la respuesta
      // Limpiar imágenes existentes
      this.categorias.forEach(cat => cat.imagenes = []);
      
      // Asignar imágenes a categorías
      const imagenes = response.imagenes;
      for (let img of imagenes) {
        const cat = this.categorias.find(c => c.key === img.categoria);
        if (cat && cat.imagenes.length < cat.max) {
          cat.imagenes.push(img.url);
        }
      }
      
      // Opcional: guardar info del ingreso
      this.infoIngreso = response.ingreso;
    },
    error: (err) => this.alertService.error('Error cargando imágenes', err.message)
  });
}

  // Métodos para Drag & Drop
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
      this.alertService.warning(
        'Límite alcanzado',
        `Esta categoría solo permite ${categoria.max} imagen(es).`
      );
      return;
    }

    const files = event.dataTransfer?.files;
    if (!files || files.length === 0) return;

    // Convertir FileList a Array de Files explícitamente
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

    // Verificar límite de imágenes
    const totalDespuesDeSubir = categoria.imagenes.length + imageFiles.length;
    if (totalDespuesDeSubir > categoria.max) {
      const disponibles = categoria.max - categoria.imagenes.length;
      this.alertService.warning(
        'Demasiadas imágenes',
        `Solo puedes subir ${disponibles} imagen(es) más para esta categoría.`
      );
      return;
    }

    this.subirArchivos(imageFiles, categoria);
  }

  // Método para subir archivos
 // Método para subir archivos
subirArchivos(files: File[], categoria: Categoria) {
  const formData = new FormData();
  
  // Agregar cada archivo al FormData
  files.forEach(file => {
    formData.append('imagenes[]', file);
  });
  
  formData.append('categoria', categoria.key);

  this.service.uploadImagen(this.id, formData).subscribe({
    next: (res) => {
      // Agregar las nuevas imágenes a las existentes
      // res.imagenes contiene las URLs de las imágenes recién subidas
      categoria.imagenes = [...categoria.imagenes, ...res.imagenes];
      
      // Asegurarse de no exceder el límite (por si acaso)
      if (categoria.imagenes.length > categoria.max) {
        categoria.imagenes = categoria.imagenes.slice(0, categoria.max);
      }
      
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

    // Convertir FileList a Array de Files
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

    // Verificar límite
    const totalDespuesDeSubir = categoria.imagenes.length + imageFiles.length;
    if (totalDespuesDeSubir > categoria.max) {
      const disponibles = categoria.max - categoria.imagenes.length;
      this.alertService.warning(
        'Demasiadas imágenes',
        `Solo puedes subir ${disponibles} imagen(es) más para esta categoría.`
      );
      return;
    }

    this.subirArchivos(imageFiles, categoria);

    // Resetear el input file
    event.target.value = '';
  }

  eliminarImagen(categoria: Categoria, url: string) {
    this.alertService.confirm({
      title: '¿Eliminar imagen?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        this.service.deleteImagen(this.id, categoria.key, url).subscribe({
          next: () => {
            categoria.imagenes = categoria.imagenes.filter(i => i !== url);
            this.alertService.success('Imagen eliminada');
          },
          error: () => this.alertService.error('No se pudo eliminar la imagen')
        });
      }
    });
  }

  abrirModal(img: string) {
    this.imagenSeleccionada = img;
    const modal = new bootstrap.Modal(document.getElementById('modalImagen')!);
    modal.show();
  }

  // Método auxiliar para verificar si se pueden subir más imágenes
  private puedeSubirMasImagenes(categoria: Categoria): boolean {
    return categoria.imagenes.length < categoria.max;
  }

  // Método para mostrar slots vacíos
  getEmptySlots(categoria: Categoria): number[] {
    const emptySlots = categoria.max - categoria.imagenes.length;
    return emptySlots > 0 ? Array(emptySlots).fill(0) : [];
  }
}