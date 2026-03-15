export interface Permiso {
  id: number;
  name: string;
}

export interface Rol {
  id: number;
  name: string;
  permisos: Permiso[];
}
