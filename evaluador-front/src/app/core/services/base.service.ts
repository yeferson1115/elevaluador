// src/app/services/base.service.ts
import { HttpErrorResponse } from '@angular/common/http';
import { throwError } from 'rxjs';

export class BaseService {
  protected handleError(error: HttpErrorResponse) {
    let message = 'Ha ocurrido un error inesperado.';

    if (error.status === 422 && error.error?.errors) {
      const firstKey = Object.keys(error.error.errors)[0];
      message = error.error.errors[firstKey][0];
    } else if (typeof error.error === 'string') {
      message = error.error;
    } else if (error.error?.message) {
      message = error.error.message;
    }

    return throwError(() => new Error(message));
  }
}
