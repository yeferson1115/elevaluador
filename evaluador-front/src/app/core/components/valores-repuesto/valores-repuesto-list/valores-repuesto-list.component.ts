import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ValoresRepuestosService } from '../../../services/valores-repuestos.service'; 
import { AlertService } from '../../../services/alert.service';
import { ValoresRepuesto, PaginatedResponse } from '../../../models/valores-repuesto.model';

@Component({
  selector: 'app-valores-repuesto-list',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './valores-repuesto-list.component.html'
})
export class ValoresRepuestoListComponent implements OnInit {
  valoresRepuestos: ValoresRepuesto[] = [];
  loading = false;
  
  // Paginación
  currentPage = 1;
  pageSize = 10;
  totalItems = 0;
  totalPages = 0;
  
  // Filtros
  filterTipo = '';
  tipos: string[] = [];
  
  // Para la paginación en la vista
  pages: number[] = [];

  // Para usar Math en la plantilla
  Math = Math;

  constructor(
    private valoresRepuestoService: ValoresRepuestosService,
    private router: Router,
    private alertService: AlertService
  ) { }

  ngOnInit(): void {
    this.loadTipos();
    this.loadData();
  }

  loadData(): void {
    this.loading = true;
    
    const params = {
      page: this.currentPage,
      per_page: this.pageSize,
      tipo: this.filterTipo || undefined
    };

    this.valoresRepuestoService.getValoresRepuestos(params)
      .subscribe({
        next: (response: PaginatedResponse) => {
          console.log('Respuesta procesada:', response);
          
          // Ahora response es directamente el PaginatedResponse
          this.valoresRepuestos = response.data || [];
          this.totalItems = response.total || 0;
          this.totalPages = response.last_page || 1;
          this.currentPage = response.current_page || 1;
          
          console.log('valoresRepuestos asignado:', this.valoresRepuestos);
          console.log('Es array?', Array.isArray(this.valoresRepuestos));
          
          this.calculatePages();
          this.loading = false;
        },
        error: (error) => {
          console.error('Error loading data', error);
          this.valoresRepuestos = [];
          this.alertService.error('Error al cargar los datos');
          this.loading = false;
        }
      });
  }

  loadTipos(): void {
    this.valoresRepuestoService.getTipos().subscribe({
      next: (tipos: string[]) => {
        console.log('Tipos recibidos:', tipos);
        this.tipos = tipos || [];
      },
      error: (error) => {
        console.error('Error loading tipos', error);
        this.tipos = [];
        // Extraer tipos de los datos si es posible
        this.extractTiposFromData();
      }
    });
  }

  extractTiposFromData(): void {
    if (this.valoresRepuestos.length > 0) {
      const tiposSet = new Set(this.valoresRepuestos.map(item => item.tipo));
      this.tipos = Array.from(tiposSet).sort();
    }
  }

  calculatePages(): void {
    this.pages = [];
    if (this.totalPages <= 1) return;
    
    const maxVisiblePages = 5;
    let startPage = Math.max(1, this.currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(this.totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
      this.pages.push(i);
    }
  }

  onPageChange(page: number): void {
    if (page < 1 || page > this.totalPages || page === this.currentPage) {
      return;
    }
    this.currentPage = page;
    this.loadData();
  }

  applyFilter(): void {
    this.currentPage = 1;
    this.loadData();
  }

  clearFilter(): void {
    this.filterTipo = '';
    this.applyFilter();
  }

  createNew(): void {
    this.router.navigate(['/admin/valores-repuesto/nuevo']);
  }

  viewDetail(id: number): void {
    this.router.navigate(['/admin/valores-repuesto', id]);
  }

  editItem(id: number): void {
    this.router.navigate(['/admin/valores-repuesto/editar', id]);
  }

  deleteItem(item: ValoresRepuesto): void {
    this.alertService.confirm({
      title: '¿Eliminar registro?',
      text: `¿Está seguro de eliminar el registro de ${item.tipo} - ${item.cilindraje_to}cc a ${item.cilindraje_from}?`,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        this.loading = true;
        this.valoresRepuestoService.deleteValoresRepuesto(item.id!)
          .subscribe({
            next: (response) => {
              if (response && response.success) {
                this.alertService.success('Registro eliminado exitosamente');
                this.loadData();
              } else {
                this.alertService.error(response?.message || 'Error al eliminar');
                this.loading = false;
              }
            },
            error: (error) => {
              console.error('Error deleting', error);
              this.alertService.error('Error al eliminar el registro');
              this.loading = false;
            }
          });
      }
    });
  }

  formatCurrency(value: number | string | null): string {
    if (value === null || value === undefined) return '-';
    
    // Convertir a número si es string
    const numValue = typeof value === 'string' ? parseFloat(value) : value;
    
    if (isNaN(numValue)) return '-';
    
    return new Intl.NumberFormat('es-CO', { 
      style: 'currency', 
      currency: 'COP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(numValue);
  }
}