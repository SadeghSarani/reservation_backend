import { ReservationStrategy } from './reservation.strategy';
import { Reservation } from '../reservation.entity';
import { Repository } from 'typeorm';
import { CreateReservationDto } from '../dto/create-reservation.dto';
import { Indoor } from '../../indoors/indoor.entity';
export declare class MonthlyReservationStrategy implements ReservationStrategy {
    private reservationRepo;
    constructor(reservationRepo: Repository<Reservation>);
    validate(dto: CreateReservationDto, indoor: Indoor): Promise<void>;
    create(dto: CreateReservationDto, userId: number, indoor: Indoor): Promise<Reservation>;
}
