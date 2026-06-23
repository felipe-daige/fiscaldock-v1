<?php

namespace App\Support\Reports;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Wrapper fino sobre OpenSpout para planilhas de relatório, dirigido pelos
 * tokens de ReportTheme (cores vêm de ReportTheme::statusHex/riscoHex).
 * Estilo: título de marca, header congelado, célula colorida, linha de totais.
 *
 * Uso: XlsxReport::paraArquivo($path)->addSheet(..)->header(..)->linha(..)->fechar();
 */
final class XlsxReport
{
    private Writer $writer;

    private bool $primeiraSheet = true;

    /** Linhas já escritas na sheet corrente (para calcular o freeze do header). */
    private int $linhaAtual = 0;

    public static function disponivel(): bool
    {
        return class_exists(Writer::class);
    }

    public static function paraArquivo(string $path): self
    {
        $self = new self();
        $self->writer = new Writer(new Options());
        $self->writer->openToFile($path);

        return $self;
    }

    public function addSheet(string $nome): self
    {
        $nome = mb_substr($nome, 0, 31); // limite de nome de aba do Excel

        if ($this->primeiraSheet) {
            // openToFile já criou a primeira aba — só renomeia
            $this->primeiraSheet = false;
        } else {
            $this->writer->addNewSheetAndMakeItCurrent();
        }

        $this->writer->getCurrentSheet()->setName($nome);
        $this->linhaAtual = 0;

        return $this;
    }

    public function tituloMarca(string $titulo): self
    {
        $style = (new Style())->setFontBold()->setFontSize(14);
        $this->writer->addRow(Row::fromValues([$titulo], $style));
        $this->linhaAtual++;

        return $this;
    }

    public function header(array $colunas): self
    {
        $style = (new Style())->setFontBold()->setBackgroundColor('F3F4F6');
        $this->writer->addRow(Row::fromValues(array_values($colunas), $style));
        $this->linhaAtual++;

        // Congela tudo acima da próxima linha (header e o que vier antes ficam fixos).
        $this->writer->getCurrentSheet()->setSheetView(
            (new SheetView())->setFreezeRow($this->linhaAtual + 1)
        );

        return $this;
    }

    /**
     * @param  array<int,mixed>  $valores
     * @param  array<int,string>  $coresPorIndice  índice 0-based da coluna => hex (#opcional)
     */
    public function linha(array $valores, array $coresPorIndice = []): self
    {
        if ($coresPorIndice === []) {
            $this->writer->addRow(Row::fromValues(array_values($valores)));
            $this->linhaAtual++;

            return $this;
        }

        $cells = [];
        foreach (array_values($valores) as $i => $v) {
            $hex = $coresPorIndice[$i] ?? null;
            if ($hex !== null && $hex !== '') {
                $style = (new Style())
                    ->setBackgroundColor(strtoupper(ltrim((string) $hex, '#')))
                    ->setFontColor(Color::WHITE)
                    ->setFontBold();
                $cells[] = Cell::fromValue($v, $style);
            } else {
                $cells[] = Cell::fromValue($v);
            }
        }
        $this->writer->addRow(new Row($cells));
        $this->linhaAtual++;

        return $this;
    }

    public function totais(array $valores): self
    {
        $style = (new Style())->setFontBold()->setBackgroundColor('E5E7EB');
        $this->writer->addRow(Row::fromValues(array_values($valores), $style));
        $this->linhaAtual++;

        return $this;
    }

    public function fechar(): void
    {
        $this->writer->close();
    }
}
