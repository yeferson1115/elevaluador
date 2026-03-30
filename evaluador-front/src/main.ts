import { bootstrapApplication } from '@angular/platform-browser';
import { provideRouter } from '@angular/router';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { AppComponent } from './app/app.component';
import { routes } from './app/app.routes';
import { JwtInterceptor } from './app/core/interceptors/jwt.interceptor';
import { jwtRefreshInterceptor } from './app/core/interceptors/auth/jwt-refresh.interceptor';
import { loaderInterceptor } from './app/core/interceptors/loader.interceptor';
import { offlineQueueInterceptor } from './app/core/interceptors/offline-queue.interceptor';

bootstrapApplication(AppComponent, {
  providers: [
    provideRouter(routes),
    provideHttpClient(withInterceptors([JwtInterceptor,jwtRefreshInterceptor,offlineQueueInterceptor,loaderInterceptor])),
  ],
});

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    void navigator.serviceWorker.register('/sw.js');
  });
}
