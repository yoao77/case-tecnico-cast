/**
 * Nada de HTML/CSS montado no PHP: nem markup, nem display:none, nem classe.
 * O PHP passa só dado; a apresentação é decidida no template Twig (templates/).
 */

export const nome = 'sem-html-no-php';
export const descricao = 'PHP passa dado, não markup — HTML/CSS ficam no template Twig';

const ALVO = /(^|\/)src\/(Service|Repository|Controller)\/.+\.php$/;
const COMENTARIO = /^\s*(\/\/|#|\*|\/\*)/;

const PADROES = [
  {
    // tag só conta se "<" vier colado no nome — evita pegar "$a < $b"
    re: /<\/?(div|span|table|thead|tbody|tr|td|th|ul|ol|li|p|br|hr|strong|em|img|button|input|select|option|form|label|h[1-6])\b/i,
    msg: 'markup HTML',
    dica: 'devolva o dado e monte o markup no template Twig',
  },
  {
    re: /style\s*=\s*['"]/i,
    msg: 'CSS inline',
    dica: 'estilo é responsabilidade do template Twig',
  },
  {
    re: /display\s*:\s*none/i,
    msg: 'display:none decidido no PHP',
    dica: 'devolva a flag e deixe o template decidir o que esconder',
  },
  {
    re: /class\s*=\s*['"]/i,
    msg: 'classe CSS montada no PHP',
    dica: 'devolva o dado/estado e deixe o template escolher a classe',
  },
];

export default function ({ arquivos }) {
  const problemas = [];

  for (const arquivo of arquivos) {
    if (!ALVO.test(arquivo.caminho)) continue;

    for (const { numero, texto } of arquivo.linhasAdicionadas()) {
      if (COMENTARIO.test(texto)) continue;

      for (const padrao of PADROES) {
        if (!padrao.re.test(texto)) continue;

        problemas.push({
          arquivo: arquivo.caminho,
          linha: numero,
          mensagem: `${padrao.msg}: ${texto.trim()}`,
          dica: padrao.dica,
        });
        break; // um problema por linha já basta
      }
    }
  }

  return problemas;
}
