import { User } from '../users/user.entity';
import { Indoor } from '../indoors/indoor.entity';
import { ReservationType } from '../common/enums/reservation-type.enum';
export declare class Reservation {
    id: number;
    user: User;
    indoor: Indoor;
    type: ReservationType;
    startTime: Date;
    endTime: Date;
    month: string;
    status: string;
    createdAt: Date;
    price: number;
}
