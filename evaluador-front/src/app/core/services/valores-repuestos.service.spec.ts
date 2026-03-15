import { TestBed } from '@angular/core/testing';

import { ValoresRepuestosService } from './valores-repuestos.service';

describe('ValoresRepuestosService', () => {
  let service: ValoresRepuestosService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(ValoresRepuestosService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
