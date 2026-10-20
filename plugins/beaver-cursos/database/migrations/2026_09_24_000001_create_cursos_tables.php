<?php
/**
 * Beaver Cursos — tabelas base.
 */

use Beaver\Database\Migrations\Migration;
use Beaver\Database\Migrations\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Cursos
        Schema::create('cursos', [
            'id',
            'slug:str',
            'titulo:str',
            'descricao:text?',
            'preco:str=49.00',
            'duracao_min:int=35',
            'licenca_key:str?',
            'pack_path:str?',
            'ativo:bool=1',
            'timestamps',
        ]);

        // Questões
        Schema::create('questoes', [
            'id',
            'curso_id:int',
            'ordem:int',
            'tipo:str',
            'enunciado:text',
            'opcoes:text?',
            'resposta:text',
            'pontos:int=1',
        ]);

        // Tentativas
        Schema::create('tentativas', [
            'id',
            'user_id:int',
            'curso_id:int',
            'started_at:str',
            'ends_at:str',
            'finished_at:str?',
            'score:int=0',
            'total:int=0',
            'status:str=em_curso',
        ]);

        // Respostas
        Schema::create('respostas', [
            'id',
            'tentativa_id:int',
            'questao_id:int',
            'resposta:text?',
            'correta:bool=0',
            'answered_at:str?',
        ]);

        // Constraints extra (SQL directo — mais seguro)
        try {
            db()->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_cursos_slug ON cursos(slug)');
            db()->exec('CREATE INDEX IF NOT EXISTS ix_questoes_curso ON questoes(curso_id, ordem)');
            db()->exec('CREATE INDEX IF NOT EXISTS ix_tentativas_user ON tentativas(user_id, status)');
            db()->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_respostas_pair ON respostas(tentativa_id, questao_id)');
        } catch (\Throwable $e) {
            // Ignorar — constraints podem já existir
        }
    }

    public function down(): void
    {
        Schema::drop('respostas');
        Schema::drop('tentativas');
        Schema::drop('questoes');
        Schema::drop('cursos');
    }
};
