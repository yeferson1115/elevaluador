export interface ValoresRepuesto {
    id?: number;
    cilindrage: string;
    tipo: string;
    llantas: number | null;
    tapiceria: number | null;
    soat: number | null;
    rtm: number | null;
    kit_arrastre: number | null;
    motor_mantenimiento: number | null;
    pintura: number | null;
    latoneria: number | null;
    chasis: number | null;
    frenos: number | null;
    bateria: number | null;
    tanque_combustible: number | null;
    llave: number | null;
    sis_electrico: number | null;
    created_at?: string;
    updated_at?: string;
}

export interface ValoresRepuestoResponse {
    success: boolean;
    message: string;
    data: ValoresRepuesto | ValoresRepuesto[] | null;
    errors?: any;
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export interface TipoResponse {
    success: boolean;
    message: string;
    data: string[];
}

export interface CilindrageResponse {
    success: boolean;
    message: string;
    data: string[];
}