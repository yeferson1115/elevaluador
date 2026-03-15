import { bootstrapApplication } from '@angular/platform-browser';
import { provideRouter } from '@angular/router';
import { importProvidersFrom } from '@angular/core';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { AppComponent } from './app/app.component';
import { routes } from './app/app.routes';
import { JwtInterceptor } from './app/core/interceptors/jwt.interceptor';
import { jwtRefreshInterceptor } from './app/core/interceptors/auth/jwt-refresh.interceptor';
import { loaderInterceptor } from './app/core/interceptors/loader.interceptor';

bootstrapApplication(AppComponent, {
  providers: [
    provideRouter(routes),
    provideHttpClient(withInterceptors([JwtInterceptor,jwtRefreshInterceptor,loaderInterceptor])),
  ],
});