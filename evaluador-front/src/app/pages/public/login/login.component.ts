import { Component, OnInit } from '@angular/core';
import { AuthService } from '../../../core/services/auth.service';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  standalone: true,
  selector: 'app-login',
  imports: [FormsModule, CommonModule],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css'],
})
export class LoginComponent implements OnInit {
  email = '';
  password = '';

  constructor(private auth: AuthService, private router: Router) {}

  ngOnInit(): void {
    // 🔹 Borra todo el almacenamiento local al entrar al login
    localStorage.clear();
    sessionStorage.clear(); // opcional, por si también usas sessionStorage
  }

  login() {
    this.auth.login({ email: this.email, password: this.password }).subscribe(() => {
      this.router.navigate(['/admin']);
    });
  }
}
