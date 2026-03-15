export interface ValoresRepuesto {
    id?: number;
    cilindraje_to: string;
    cilindraje_from: string;
    tipo: string;
    llantas: number | string | null;
    tapiceria: number | string | null;
    soat: number | string | null;
    rtm: number | string | null;
    kit_arrastre: number | string | null;
    motor_mantenimiento: number | string | null;
    pintura: number | string | null;
    latoneria: number | string | null;
    chasis: number | string | null;
    frenos: number | string | null;
    bateria: number | string | null;
    tanque_combustible: number | string | null;
    llave: number | string | null;
    sis_electrico: number | string | null;
    created_at?: string | null;
    updated_at?: string | null;
}

export interface PaginatedResponse {
    current_page: number;
    data: ValoresRepuesto[];  // Aquí está el array
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

export interface ApiResponseWrapper<T> {
    success: boolean;
    message: string;
    data: T;  // Puede ser PaginatedResponse o un array o un objeto
    errors?: any;
}

// Tipo específico para la respuesta de lista
export type ValoresRepuestoListResponse = ApiResponseWrapper<PaginatedResponse>;