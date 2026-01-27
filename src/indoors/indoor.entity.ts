import {
  Column,
  CreateDateColumn,
  Entity,
  ManyToOne,
  PrimaryGeneratedColumn,
} from 'typeorm';
import { IndoorType } from '../common/enums/indoor-type.enum';
import { User } from '../users/user.entity';

@Entity()
export class Indoor {
  @PrimaryGeneratedColumn()
  id: number;

  @Column()
  name: string;

  @Column({ type: 'enum', enum: IndoorType })
  type: IndoorType;

  @Column({ default: true })
  isActive: boolean;

  @CreateDateColumn()
  createdAt: Date;

  @ManyToOne(() => User)
  owner: User;

  @Column({ type: 'int', nullable: true })
  pricePerHour: number;

  @Column({ type: 'int', nullable: true })
  pricePerMonth: number;
}
