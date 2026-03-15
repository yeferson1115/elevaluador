import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { UserService, User } from '../../../core/services/user.service';
import { FormsModule } from '@angular/forms';
import { UserFormComponent } from './user-form.component';
import { Modal } from 'bootstrap';
import { AlertService } from '../../../core/services/alert.service'; 
import { Permissions } from '../../../core/constants/permissions.const';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-user-list',
  standalone: true,
  imports: [CommonModule, FormsModule, UserFormComponent],
  templateUrl: './user-list.component.html',
})
export class UserListComponent {
  Permissions = Permissions;
  users = signal<User[]>([]);
  page = signal(1);
  perPage = signal(10);
  totalPages = signal(1);
  search = '';
  editingUser = signal<User | null>(null);

  private userModal?: Modal;

  constructor(
    private userService: UserService,
    private alertService: AlertService,
    private authService: AuthService,
  ) {
    this.loadUsers();
  }

  loadUsers() {
    this.userService.getUsers(this.page(), this.search, this.perPage()).subscribe((res) => {
      this.users.set(res.data);
      this.page.set(res.current_page);
      this.totalPages.set(res.last_page);
    });
  }

  totalPagesArray() {
    return Array.from({ length: this.totalPages() }, (_, i) => i + 1);
  }

  changePage(p: number) {
    if (p < 1 || p > this.totalPages()) return;
    this.page.set(p);
    this.loadUsers();
  }

  openNewUserForm() {
    this.editingUser.set({
      id: 0,
      name: '',
      document: '',
      phone: '',
      email: '',
      r_aa:'',
      tarjeta_profecional:'',
      profesion:'',
      roles: [],
      permissions: [],
    });
    this.showModal();
  }

  editUser(user: User) {
    this.editingUser.set(user);
    this.showModal();
  }

  async deleteUser(id: number) {
    const confirmed = await this.alertService.confirm({
      title: 'Eliminar usuario',
      text: '¿Seguro que quieres eliminar este usuario?',
      icon: 'warning',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
    });

    if (confirmed) {
      this.userService.deleteUser(id).subscribe({
        next: () => {
          this.alertService.success('Usuario eliminado correctamente');
          this.loadUsers();
        },
        error: () => {
          this.alertService.error('Ocurrió un error al eliminar el usuario');
        }
      });
    }
  }

  cancelEdit() {
    this.editingUser.set(null);
    this.hideModal();
  }

  onUserSaved() {
    this.editingUser.set(null);
    this.loadUsers();
    this.hideModal();
  }

  private showModal() {
    if (!this.userModal) {
      const modalElement = document.getElementById('userModal');
      if (modalElement) {
        this.userModal = new Modal(modalElement);
      }
    }
    this.userModal?.show();
  }

  private hideModal() {
    this.userModal?.hide();
  }

   hasPermission(permission: string): boolean {
    return this.authService.hasPermission(permission);
  }
}
