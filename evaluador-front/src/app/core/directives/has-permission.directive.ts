import { Directive, Input, TemplateRef, ViewContainerRef,inject  } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { CommonModule } from '@angular/common';


@Directive({
  selector: '[hasPermission]',
  standalone: true,
})
export class HasPermissionDirective {
  private authService = inject(AuthService);

  constructor(
    private templateRef: TemplateRef<any>,
    private viewContainer: ViewContainerRef
  ) {}

  @Input() set hasPermission(permission: string) {
    if (this.authService.hasPermission(permission)) {
      this.viewContainer.createEmbeddedView(this.templateRef);
    } else {
      this.viewContainer.clear();
    }
  }
}
