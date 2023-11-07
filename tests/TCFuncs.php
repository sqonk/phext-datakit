<?php

use sqonk\phext\datakit\Importer as import;

define('IRISCSV', __DIR__.'/../docs/iris.data');

function boxPlot(bool $writeToFile = false): array
{
  $columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

  $dataset = import::csv_dataframe(IRISCSV, $columns);
  array_pop($columns);

  $plot = $dataset->box(...$columns);
  $images = $plot->render(400, 300, false);
    
  if ($writeToFile) {
    foreach ($images as $i => $img) {
      file_put_contents("plots/box_$i.png", $img);
    }
  }
    
  return $images;
}

function stockplot(bool $writeToFile = false): string
{
  $data = [
      1 => ['o' => 3, 'c' => 6, 'l' => 2, 'h' => 6],
      2 => ['o' => 6, 'c' => 4, 'l' => 3, 'h' => 6],
      3 => ['o' => 4, 'c' => 6, 'l' => 1, 'h' => 8],
      4 => ['o' => 3, 'c' => 2, 'l' => 1, 'h' => 7],
      5 => ['o' => 2, 'c' => 6, 'l' => 2, 'h' => 9]
  ];
  $df = dataframe($data);
    
  $plot = $df->stock('o', 'c', 'l', 'h', options:[
      'title' => 'stock plot',
      'font' => FF_FONT1,
      'margin' => [55,55,55,55],
      'configCallback' => function ($chart) {
        $chart->yscale->ticks->SupressZeroLabel(false);
        $chart->xscale->ticks->SupressZeroLabel(false);
        $chart->SetClipping(false);
      }
  ]);
    
  [$img] = $plot->render(700, 500, false);
    
  if ($writeToFile) {
    file_put_contents('plots/candlesticks.png', $img);
  }
    
  return $img;
}

function histogram(bool $writeToFile = false): array
{
  $columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

  $dataset = import::csv_dataframe(IRISCSV, $columns);
  array_pop($columns);
    
  $plot = $dataset->hist([
      'columns' => $columns
  ]);
  $images = $plot->render(400, 300, false);
    
  if ($writeToFile) {
    foreach ($images as $i => $img) {
      file_put_contents("plots/hist_$i.png", $img);
    }
  }
    
  return $images;
}

function histogramWithBins(bool $writeToFile = false): array
{
  $columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

  $dataset = import::csv_dataframe(IRISCSV, $columns);
  array_pop($columns);
  $plot = $dataset->hist(['columns' => $columns, 'bins' => 5, 'title' => 'hist8']);
  $images = $plot->render(400, 300, false);
    
  if ($writeToFile) {
    foreach ($images as $i => $img) {
      file_put_contents("plots/histbin_$i.png", $img);
    }
  }
    
  return $images;
}

function cumulativeHistogram(bool $writeToFile = false): array
{
  $columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

  $dataset = import::csv_dataframe(IRISCSV, $columns);
  array_pop($columns);
  $plot = $dataset->hist(['columns' => $columns, 'cumulative' => true]);
  $images = $plot->render(400, 300, false);
    
  if ($writeToFile) {
    foreach ($images as $i => $img) {
      file_put_contents("plots/histcum_$i.png", $img);
    }
  }
    
  return $images;
}

function genericPlot(bool $writeToFile = false): array
{
  $columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

  $dataset = import::csv_dataframe(IRISCSV, $columns);
  array_pop($columns);
    
  $plot = $dataset->plot('line', ['columns' => $columns, 'one' => true, 'font' => FF_FONT1]);
  $images = $plot->render(700, 500, false);
    
  if ($writeToFile) {
    foreach ($images as $i => $img) {
      file_put_contents("plots/gen_$i.png", $img);
    }
  }
    
  return $images;
}
