/**
 * SQL só no Repository.
 * Controller, Service e o resto do src/ pedem os dados ao Repository; quem fala com o banco
 * é o Repository (e o src/Database, que cria a conexão e as tabelas).
 */

export const nome = 'sql-so-no-repository';
export const descricao = 'consulta ao banco fora do Repository';

const ALVO = /^src\/.+\.php$/;
const PERMITIDOS = /^src\/(Repository|Database)\//;
const PADRAO = /->(prepare|query|exec)\s*\(/;
const COMENTARIO = /^\s*(\/\/|#|\*|\/\*)/;

export default function ({ arquivos }) {
  const problemas = [];

  for (const arquivo of arquivos) {
    if (!ALVO.test(arquivo.caminho)) continue;
    if (PERMITIDOS.test(arquivo.caminho)) continue;

    for (const { numero, texto } of arquivo.linhasAdicionadas()) {
      if (COMENTARIO.test(texto)) continue;
      if (!PADRAO.test(texto)) continue;

      problemas.push({
        arquivo: arquivo.caminho,
        linha: numero,
        mensagem: 'SQL fora do Repository: ' + texto.trim(),
        dica: 'crie um método no Repository e chame ele daqui',
      });
    }
  }

  return problemas;
}
