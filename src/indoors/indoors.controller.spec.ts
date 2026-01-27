import { Test, TestingModule } from '@nestjs/testing';
import { IndoorsController } from './indoors.controller';

describe('IndoorsController', () => {
  let controller: IndoorsController;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      controllers: [IndoorsController],
    }).compile();

    controller = module.get<IndoorsController>(IndoorsController);
  });

  it('should be defined', () => {
    expect(controller).toBeDefined();
  });
});
