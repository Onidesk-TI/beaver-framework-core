<?php

declare(strict_types=1);

namespace Beaver\Plugins\Cursos;

use Beaver\Http\Response;
use Beaver\Http\Request;
use Beaver\Plugins\Cursos\Models\Curso;
use Beaver\Plugins\Cursos\Models\Questao;
use Beaver\Plugins\Cursos\Models\Resposta;
use Beaver\Plugins\Cursos\Models\Tentativa;

class CursoController
{
    // ── LISTAGEM PAGINADA (todos os cursos) ──
    public function index(): Response
    {
        $pagina  = max(1, (int)($_GET["page"] ?? 1));
        $porPag  = 9;

        $total    = \Beaver\Plugins\Cursos\Models\Curso::where("ativo", "=", 1)->count();
        $paginas  = max(1, (int)ceil($total / $porPag));
        $pagina   = min($pagina, $paginas);
        $offset   = ($pagina - 1) * $porPag;

        $cursos = \Beaver\Plugins\Cursos\Models\Curso::where("ativo", "=", 1)
            ->orderBy("titulo")
            ->limit($porPag)
            ->offset($offset)
            ->get();

       // Enriquecer cada curso com imagem (automática se não tiver)
        $cursosArr = [];
        foreach ($cursos as $c) {
            $row = $c->toArray();
            $row['imagem'] = $this->imagemDoCurso($row);
            $cursosArr[] = $row;
        }


        return Response::html(view("beaver-cursos::lista", [
            "cursos"   => $cursosArr,
            "total"    => $total,
            "pagina"   => $pagina,
            "paginas"  => $paginas,
            "porPag"   => $porPag,
            "user"     => $this->user(),
        ]));
    }

    // ── Imagem automática (stock determinística) ──────────────
    // ── Imagem automática (stock local determinística) ──────
    private function imagemDoCurso(array $curso): string
    {
        // 1) Imagem explícita no curso.json → usa-a sempre
        if (!empty($curso['imagem'])) {
            return (string) $curso['imagem'];
        }

        // 2) Pool de imagens locais (ordem estável)
        $stock = [
        'fundamentos.jpg',
        'design.jpg',
        'api.jpg',
        'deploy.jpg',
        'testes.jpg',
        'seguranca.jpg',
        ];

        // 3) Escolha determinística pelo slug
        $slug = (string) ($curso['slug'] ?? $curso['id'] ?? 'beaver');
        $i    = crc32($slug) % count($stock);

        return '/plugins/beaver-cursos/ui/img/' . $stock[$i];
    }


    // ── LANDING ──────────────────────────────
    public function landing(Request $req, ?string $slug = null): Response
    {
        $slug = (string)($slug ?? $_GET['curso'] ?? '');

        // Sem slug → listagem paginada
        if ($slug === '') {
            return $this->index();
        }

        $curso = Curso::porSlug($slug);

        // Slug inválido → volta para a listagem
        if (!$curso) {
            return $this->index();
        }

        $user = $this->user();
        $tentativa = $user
            ? Tentativa::ultimaDoUser((int)$user['id'], (int)$curso->id)
            : null;

        return Response::html(view('beaver-cursos::landing', [
            'curso'         => $curso->toArray(),
            'user'          => $user,
            'tentativa'     => is_object($tentativa) ? $tentativa->toArray() : $tentativa,
            'totalQuestoes' => $curso->totalQuestoes(),
        ]));
    }
    // ── LOGIN ────────────────────────────────
    public function login(Request $req): Response
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');

        if (!$email || !$pass) {
            return Response::json(['ok' => false, 'erro' => 'Preenche email e password.'], 400);
        }

        // Tabela users — lê via PDO direto (evita dependência do admin)
        $u = \Beaver\Database\Database::getInstance()
            ->selectOne('SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1', [$email]);

        if (!$u) {
            return Response::json(['ok' => false, 'erro' => 'Credenciais inválidas.'], 401);
        }


        if (!password_verify($pass, (string)$u['password'])) {
            return Response::json(['ok' => false, 'erro' => 'Credenciais inválidas.'], 401);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['user_id']   = (int)$u['id'];
        $_SESSION['user_nome'] = (string)($u['name'] ?? $u['email']);

        return Response::json(['ok' => true]);
    }

    public function logout(Request $req): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION['user_id'], $_SESSION['user_nome']);
        return Response::json(['ok' => true]);
    }

    // ── INICIAR ──────────────────────────────
    public function iniciar(Request $req): Response
    {
        $user = $this->user();
        if (!$user) {
            return Response::json(['ok' => false, 'erro' => 'Precisas de iniciar sessão.'], 401);
        }

        $slug  = (string)($_POST['curso'] ?? '');
        $curso = Curso::porSlug($slug);
        if (!$curso) {
            return Response::json(['ok' => false, 'erro' => 'Curso não encontrado.'], 404);
        }

        $ativa = Tentativa::ativaDoUser((int)$user['id']);
        if ($ativa) {
            if ((int)$ativa->curso_id === (int)$curso->id) {
                return Response::json(['ok' => true, 'redirect' => '/cursos/' . $slug . '/teste']);
            }
            return Response::json(['ok' => false, 'erro' => 'Tens um teste em curso.'], 409);
        }

        $total = $curso->totalQuestoes();
        if ($total === 0) {
            return Response::json(['ok' => false, 'erro' => 'Sem questões.'], 400);
        }

        $now  = new \DateTimeImmutable();
        $ends = $now->add(new \DateInterval('PT' . (int)$curso->duracao_min . 'M'));

        Tentativa::create([
            'user_id'    => (int)$user['id'],
            'curso_id'   => (int)$curso->id,
            'started_at' => $now->format('Y-m-d H:i:s'),
            'ends_at'    => $ends->format('Y-m-d H:i:s'),
            'total'      => $total,
            'status'     => 'em_curso',
        ]);

        return Response::json(['ok' => true, 'redirect' => '/cursos/' . $slug . '/teste']);
    }

    // ── TESTE ────────────────────────────────
    public function teste(Request $req, string $slug): Response
    {
        $user = $this->user();
        if (!$user) {
            return Response::redirect('/cursos');
        }

        $curso = Curso::porSlug($slug);
        if (!$curso) {
            return Response::redirect('/cursos');
        }

        $t = Tentativa::ativaDoUser((int)$user['id']);
        if (!$t || (int)$t->curso_id !== (int)$curso->id) {
            return Response::redirect('/cursos/' . $slug);
        }

        if ($t->expirou()) {
            $t->expirar();
            return Response::redirect('/cursos/' . $slug . '?expirado=1');
        }

        return Response::html(view('beaver-cursos::teste', [
            'curso'     => $curso->toArray(),
            'user'      => $user,
            'tentativa' => is_object($t) ? $t->toArray() : $t,
            'questoes'  => array_map(fn($q) => $q->toArray(), $curso->questoes()),
            'respostas' => Resposta::mapaDaTentativa((int)$t->id),
        ]));
    }

    // ── RESPOSTA (AJAX) ─────────────────────
    public function resposta(Request $req): Response
    {
        $user = $this->user();
        if (!$user) {
            return Response::json(['ok' => false], 401);
        }

        $body = json_decode((string)file_get_contents('php://input'), true) ?: [];
        $qid  = (int)($body['questao_id'] ?? 0);
        $val  = (string)($body['resposta'] ?? '');

        $t = Tentativa::ativaDoUser((int)$user['id']);
        if (!$t) {
            return Response::json(['ok' => false], 403);
        }

        if ($t->expirou()) {
            $t->expirar();
            return Response::json(['ok' => false, 'erro' => 'Tempo esgotado'], 410);
        }

        $q = Questao::find($qid);
        if (!$q || (int)$q->curso_id !== (int)$t->curso_id) {
            return Response::json(['ok' => false], 403);
        }

        $correta = $q->avaliar($val);

        $existente = Resposta::paraQuestao((int)$t->id, $qid);
        if ($existente) {
            $existente->resposta    = $val;
            $existente->correta     = $correta ? 1 : 0;
            $existente->answered_at = date('Y-m-d H:i:s');
            $existente->save();
        } else {
            Resposta::create([
                'tentativa_id' => (int)$t->id,
                'questao_id'   => $qid,
                'resposta'     => $val,
                'correta'      => $correta ? 1 : 0,
                'answered_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        return Response::json(['ok' => true]);
    }

    // ── FINALIZAR ────────────────────────────
    public function finalizar(Request $req): Response
    {
        $user = $this->user();
        if (!$user) {
            return Response::json(['ok' => false], 401);
        }

        $t = Tentativa::ativaDoUser((int)$user['id']);
        if (!$t) {
            return Response::json(['ok' => false], 404);
        }

        $t->finalizar();
        $curso = Curso::find((int)$t->curso_id);

        return Response::json([
            'ok'       => true,
            'redirect' => '/cursos/' . ($curso->slug ?? '') . '/resultado',
        ]);
    }

    // ── RESULTADO ────────────────────────────
    public function resultado(Request $req, string $slug): Response
    {
        $user = $this->user();
        if (!$user) {
            return Response::redirect('/cursos');
        }

        $curso = Curso::porSlug($slug);
        if (!$curso) {
            return Response::redirect('/cursos');
        }

        $t = Tentativa::ultimaDoUser((int)$user['id'], (int)$curso->id);
        if (!$t || $t->status === 'em_curso') {
            return Response::redirect('/cursos/' . $slug);
        }

        return Response::html(view('beaver-cursos::resultado', [
            'curso'     => $curso->toArray(),
            'user'      => $user,
            'tentativa' => is_object($t) ? $t->toArray() : $t,
            'questoes'  => array_map(fn($q) => $q->toArray(), $curso->questoes()),
            'respostas' => Resposta::mapaDaTentativa((int)$t->id),
        ]));
    }

    // ── HELPERS ──────────────────────────────
    private function user(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        return [
            'id'   => (int)$_SESSION['user_id'],
            'nome' => (string)($_SESSION['user_nome'] ?? 'Aluno'),
        ];
    }
}
