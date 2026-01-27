import 'reflect-metadata';
import { DataSource } from 'typeorm';
import { User } from './users/user.entity';
import { Indoor } from './indoors/indoor.entity';
import { Reservation } from './reservations/reservation.entity';

export const AppDataSource = new DataSource({
  type: 'mysql', // or postgres
  host: 'localhost',
  port: 3306,
  username: 'root',
  password: '',
  database: 'reservation',
  entities: [User, Indoor, Reservation],
  migrations: ['src/migrations/*.ts'],
});
