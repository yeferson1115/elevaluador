export interface ImagenResponse {
  id: number;
  categoria: string;
  url: string;
}

export interface GetImagenesResponse {
  imagenes: ImagenResponse[];
  ingreso: any; 
}
