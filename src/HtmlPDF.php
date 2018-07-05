<?php

namespace HtmlPDF;

class HtmlPDF
{
	private $template;
	private $pdf;

	function __construct($template, array $config = [])
	{
		$this->pdf = new \Mpdf\Mpdf($config);
		$this->template = file_get_contents($template);
	}

	/**
	 * Define o campo a substituir
	 * Chama $this->set_template() pra definir se é array ou string
	 */
	public function set($item, $value='')
	{
		$this->set_template($this->template, $item, $value);
	}

	/**
	 * Define os campos no template informado.
	 * 
	 * $item == Array (chama $this->set_array())
	 * $item == String (transforma em array e chama recursivamente)
	 */
	private function set_template(&$template, $item, $value='')
	{
		if (is_array($item)) {

			// Procura se tem um item que precisa iterar (função @each)
			foreach ($item as $key => $value) {

				// Se tiver, então chama a função @each
				// Apaga o posição do array pra evitar uso redundante
				if (is_array($value)) {
					$this->each($key, $value);
					unset($item[$key]);
				}
			}

			// Define os campos presentes no array
			$this->set_array($template, $item);

		} else {
			// Transforma em array e chama recursivamente
			$this->set_template($template, [$item => $value]);
		}
	}

	/**
	 * Substitui os campos correspondentes ao array associativo
	 */
	private function set_array(&$template, array $array)
	{
		$keys = array_keys($array);
		$values = array_values($array);

		// Insere os separadores no nome do campo
		foreach ($keys as $key => $value) {
			$keys[$key] = "#$value#";
		}

		$template = str_replace($keys, $values, $template);
	}

	/**
	 * Itera um  array com a chave $key passada no documento
	 * (Somente array associativo)
	 */
	private function each($key, $array)
	{
		preg_match("/@each $key(.*?)@end/s", $this->template, $matches);
		$template = trim($matches[1]);
		$html = '';

		foreach ($array as $value) {
			$html .= $template;
			$this->set_template($html, $value);
		}

		$this->template = preg_replace("/@each $key(.*?)@end/s", $html, $this->template);
	}

	/**
	 * Adiciona css ao documento
	 * CSS only
	 */
	public function add_css($css)
	{
		$css = file_get_contents($css);
		$this->pdf->WriteHTML($css, 1);
	}

	/**
	 * Adiciona html ao documento
	 * HTML only
	 */
	public function add_html($html)
	{
		$html = file_get_contents($html);
		$this->pdf->WriteHTML($html, 2);
	}

	/**
	 * Mostra o documento em formato HTML
	 */
	public function print()
	{
		echo $this->template;
	}

	/**
	 * Escreve o documento e faz stream dele
	 */
	public function output()
	{
		$this->pdf->WriteHTML($this->template);
		$this->pdf->Output();
	}

	/**
	 * Escreve o documento e salva no caminho informado
	 */
	public function save($path)
	{
		$this->pdf->WriteHTML($this->template);
		$this->pdf->Output($path, \Mpdf\Output\Destination::FILE);
	}

	/**
	 * Escreve o documento e faz download
	 */
	public function download($name)
	{
		$this->pdf->WriteHTML($this->template);
		$this->pdf->Output($name, \Mpdf\Output\Destination::DOWNLOAD);
	}
}