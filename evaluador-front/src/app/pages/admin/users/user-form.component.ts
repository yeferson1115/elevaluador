import {
  Component,
  Input,
  Output,
  EventEmitter,
  OnChanges,
  SimpleChanges,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { UserService, User, Role } from '../../../core/services/user.service';
import { RolesService } from '../../../core/services/roles.service';
import { AlertService } from '../../../core/services/alert.service';

@Component({
  selector: 'app-user-form',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './user-form.component.html',
})
export class UserFormComponent implements OnChanges {
  @Input() user: User | null = null;
  @Output() saved = new EventEmitter<void>();
  @Output() cancel = new EventEmitter<void>();

  name = '';
  email = '';
  document = '';
  phone = '';
  tarjeta_profecional='';
  r_aa='';
  password = '';
  profesion='';
  passwordConfirm = '';
  selectedRoleId: number | null = null;

  availableRoles: Role[] = [];

  constructor(
    private userService: UserService,
    private roleService: RolesService,
    private alertService: AlertService // ✅ Inyecta aquí
  ) {
    this.loadAvailableRoles();
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['user'] && this.user) {
      this.name = this.user.name;
      this.email = this.user.email;
      this.document = this.user.document || '';
      this.phone = this.user.phone || '';
      this.tarjeta_profecional = this.user.tarjeta_profecional || '';
      this.r_aa = this.user.r_aa || '';
      this.profesion= this.profesion || '';
      this.selectedRoleId = this.user.roles?.[0]?.id || null;
      this.password = '';
      this.passwordConfirm = '';
    }
  }

  loadAvailableRoles() {
    this.roleService.getallRoles().subscribe({
      next: (roles) => {
        this.availableRoles = roles;
      },
      error: (err) => {
        this.alertService.error('Error al cargar los roles');
        console.error('Error cargando roles:', err);
      },
    });
  }

  save() {
    if (!this.user) return;

    if (!this.selectedRoleId) {
      this.alertService.warning('Debe seleccionar un rol');
      return;
    }

    if (!this.user.id && this.password !== this.passwordConfirm) {
      this.alertService.warning('Las contraseñas no coinciden');
      return;
    }

    const payload: any = {
      name: this.name,
      email: this.email,
      document: this.document,
      phone: this.phone,
      tarjeta_profecional: this.tarjeta_profecional,
      r_aa: this.r_aa,
      profesion:this.profesion,
      roles: [this.selectedRoleId],
    };

    if (!this.user.id) {
      payload.password = this.password;
      this.userService.createUser(payload).subscribe({
        next: () => {
          this.alertService.success('Usuario creado correctamente');
          this.saved.emit();
        },
        error: () => {
          this.alertService.error('Error al crear el usuario');
        },
      });
    } else {
      this.userService.updateUser(this.user.id, payload).subscribe({
        next: () => {
          this.alertService.success('Usuario actualizado correctamente');
          this.saved.emit();
        },
        error: () => {
          this.alertService.error('Error al actualizar el usuario');
        },
      });
    }
  }

  onCancel() {
    this.cancel.emit();
  }
}
