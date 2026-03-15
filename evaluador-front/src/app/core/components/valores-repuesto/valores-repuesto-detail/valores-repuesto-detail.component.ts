import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { ValoresRepuestosService } from '../../../services/valores-repuestos.service'; 
import { AlertService } from '../../../services/alert.service';
import { ValoresRepuesto } from '../../../models/valores-repuesto.model';

@Component({
  selector: 'app-valores-repuesto-detail',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './valores-repuesto-detail.component.html'
})
export class ValoresRepuestoDetailComponent implements OnInit {
  item?: ValoresRepuesto;
  loading = false;
  itemId!: number;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private valoresRepuestoService: ValoresRepuestosService,
    private alertService: AlertService
  ) { }

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.itemId = +id;
      this.loadData(this.itemId);
    } else {
      this.router.navigate(['/admin/valores-repuesto']);
    }
  }

  loadData(id: number): void {
    this.loading = true;
    this.valoresRepuestoService.getValoresRepuestoById(id)
      .subscribe({
        next: (valoresRepuesto: ValoresRepuesto) => {
          console.log('Datos cargados:', valoresRepuesto);
          // Ahora recibimos directamente el objeto ValoresRepuesto
          this.item = valoresRepuesto;
          this.loading = false;
        },
        error: (error) => {
          console.error('Error loading data', error);
          
          // Manejar diferentes tipos de error
          if (error.status === 404) {
            this.alertService.error('Registro no encontrado');
          } else if (error.error && error.error.message) {
            this.alertService.error(error.error.message);
          } else {
            this.alertService.error('Error al cargar los datos del registro');
          }
          
          this.loading = false;
          this.router.navigate(['/admin/valores-repuesto']);
        }
      });
  }

  goBack(): void {
    this.router.navigate(['/admin/valores-repuesto']);
  }

  edit(): void {
    this.router.navigate(['/admin/valores-repuesto/editar', this.itemId]);
  }

  formatCurrency(value: number | string | null | undefined): string {
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