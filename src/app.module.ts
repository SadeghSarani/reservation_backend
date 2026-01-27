import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { AuthModule } from './auth/auth.module';

@Module({
  imports: [
    TypeOrmModule.forRoot({
      type: 'mysql',
      host: 'localhost',
      port: 3306,
      username: 'root',
      password: '',
      database: 'reservation',
      autoLoadEntities: true,
      synchronize: false, // ❗ dev only
    }),
    AuthModule,
  ],
})
export class AppModule {}
