import { Reservation } from '../reservation.entity';
import { CreateReservationDto } from '../dto/create-reservation.dto';
import { Indoor } from '../../indoors/indoor.entity';

export interface ReservationStrategy {
  validate(dto: CreateReservationDto, indoor: Indoor): Promise<void>;

  create(
    dto: CreateReservationDto,
    userId: number,
    indoor: Indoor,
  ): Promise<Reservation>;
}
