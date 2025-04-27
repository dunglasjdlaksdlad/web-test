// src/types/Option.ts
import { ReactNode } from 'react';

export interface Option {
    label: string;
    value: string;
    count?: number;
    icon?: React.FC<React.SVGProps<SVGSVGElement>>;
}