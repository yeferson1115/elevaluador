import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { RolesService } from '../../../core/services/roles.service';
import { ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { AlertService } from '../../../core/services/alert.service'; 
import { Location } from '@angular/common';


interface Permission {
  id: number;
  name: string;
}

@Component({
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  selector: 'app-role-form',
  templateUrl: './role-form.component.html'
})
export class RoleFormComponent implements OnInit {
  form: FormGroup;
  permissions: Permission[] = [];
  isEdit = false;
  roleId: number | null = null; // si estás editando

  constructor(private fb: FormBuilder, private rolesService: RolesService,private route: ActivatedRoute,
  private router: Router,private alert: AlertService,private location: Location ) {
    this.form = this.fb.group({
      name: [''],
      permissions: [[]] // almacenará un array de IDs
    });
  }

  ngOnInit(): void {
    this.rolesService.getPermissions().subscribe((res: Permission[]) => {
      this.permissions = res;
    });

  const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.isEdit = true;
      this.roleId = +id;
      this.rolesService.getRole(this.roleId).subscribe(role => {
        this.form.patchValue({
          name: role.name,
          permissions: role.permissions.map((p: any) => p.id)
        });
      });
    }
    // Si estás en modo edición, podrías cargar el rol y sus permisos aquí
  }

  togglePermission(id: number): void {
    const current: number[] = this.form.value.permissions || [];
    if (current.includes(id)) {
      this.form.patchValue({ permissions: current.filter((p: number) => p !== id) });
    } else {
      this.form.patchValue({ permissions: [...current, id] });
    }
  }

  hasPermission(id: number): boolean {
    return this.form.value.permissions.includes(id);
  }

  onSubmit(): void {
  const data = this.form.value;

  if (this.isEdit && this.roleId) {
    this.rolesService.updateRole(this.roleId, data).subscribe({
      next: (res) => {
        this.alert.success('Rol actualizado correctamente');
        this.location.back();
      },
      error: (err) => {
        this.alert.error(err.message || 'Error al actualizar el rol');
        console.error('Error actualizando rol', err);
      }
    });
  } else {
    this.rolesService.createRole(data).subscribe({
      next: (res) => {
        this.alert.success('Rol creado correctamente');
        this.location.back();
      },
      error: (err) => {
        this.alert.error(err.message || 'Error al crear el rol');
        console.error('Error creando rol', err);
      }
    });
  }
}

}
