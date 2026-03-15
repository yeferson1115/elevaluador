import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { HeaderComponent } from './header/header.component';
import { FooterComponent } from './footer/footer.component';

@Component({
  standalone: true,
  selector: 'app-public-layout',
  imports: [RouterOutlet,HeaderComponent,FooterComponent],
  templateUrl: './public-layout.component.html',  // Aquí la referencia al archivo HTML externo
})
export class PublicLayoutComponent {}