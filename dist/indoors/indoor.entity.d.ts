import { IndoorType } from '../common/enums/indoor-type.enum';
import { User } from '../users/user.entity';
export declare class Indoor {
    id: number;
    name: string;
    type: IndoorType;
    isActive: boolean;
    createdAt: Date;
    owner: User;
    pricePerHour: number;
    pricePerMonth: number;
}
