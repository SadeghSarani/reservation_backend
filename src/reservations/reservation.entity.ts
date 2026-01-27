import {
  Column,
  CreateDateColumn,
  Entity,
  ManyToOne,
  PrimaryGeneratedColumn,
} from 'typeorm';
import { User } from '../users/user.entity';
import { Indoor } from '../indoors/indoor.entity';
import { ReservationType } from '../common/enums/reservation-type.enum';

@Entity()
export class Reservation {
  @PrimaryGeneratedColumn()
  id: number;

  @ManyToOne(() => User)
  user: User;

  @ManyToOne(() => Indoor)
  indoor: Indoor;

  @Column({ type: 'enum', enum: ReservationType })
  type: ReservationType;

  @Column({ type: 'datetime', nullable: true })
  startTime: Date;

  @Column({ type: 'datetime', nullable: true })
  endTime: Date;

  @Column({ nullable: true })
  month: string; // YYYY-MM

  @Column({ default: 'RESERVED' })
  status: string;

  @CreateDateColumn()
  createdAt: Date;

  @Column({ type: 'int' })
  price: number;
}
