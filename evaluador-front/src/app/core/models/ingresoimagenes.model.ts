export interface ImagenResponse {
  id: number;
  categoria: string;
  url: string;
  orden: number;
  rotacion: number;
}

export interface GetImagenesResponse {
  imagenes: ImagenResponse[];
  ingreso: any; 
}
