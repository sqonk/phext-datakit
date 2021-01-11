<?php

use sqonk\phext\datakit\Importer as import;

function boxPlot(bool $writeToFile = false): array
{
    $columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

    $dataset = import::csv_dataframe(__DIR__.'/iris.data', $columns, 1);
    array_pop($columns);

    $plot = $dataset->box(...$columns);
    $images = $plot->render(400, 300, false);
    
    if ($writeToFile) {
        foreach ($images as $i => $img)
            file_put_contents("plots/box_$i.png", $img);
    }
    
    return $images;
}

function histogram(bool $writeToFile = false): array
{
    $columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

    $dataset = import::csv_dataframe(__DIR__.'/iris.data', $columns, 1);
    array_pop($columns);
    
    $plot = $dataset->hist([
        'columns' => $columns
    ]);
    $images = $plot->render(400, 300, false);
    
    if ($writeToFile) {
        foreach ($images as $i => $img)
            file_put_contents("plots/hist_$i.png", $img);
    }
    
    return $images;
}

function histogramWithBins(bool $writeToFile = false): array
{
    $columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

    $dataset = import::csv_dataframe(__DIR__.'/iris.data', $columns, 1);
    array_pop($columns);
    $plot = $dataset->hist(['columns' => $columns, 'bins' => 5, 'title' => 'hist8']);
    $images = $plot->render(400, 300, false);
    
    if ($writeToFile) {
        foreach ($images as $i => $img)
            file_put_contents("plots/histbin_$i.png", $img);
    }
    
    return $images;
}

function cumulativeHistogram(bool $writeToFile = false): array
{
    $columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

    $dataset = import::csv_dataframe(__DIR__.'/iris.data', $columns, 1);
    array_pop($columns);
    $plot = $dataset->hist(['columns' => $columns, 'cumulative' => true]);
    $images = $plot->render(400, 300, false);
    
    if ($writeToFile) {
        foreach ($images as $i => $img)
            file_put_contents("plots/histcum_$i.png", $img);
    }
    
    return $images;
}

function genericPlot(bool $writeToFile = false): array
{
    $columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

    $dataset = import::csv_dataframe(__DIR__.'/iris.data', $columns, 1);
    array_pop($columns);
    
    $plot = $dataset->plot('line', ['columns' => $columns, 'one' => true, 'font' => FF_FONT1]);
    $images = $plot->render(700, 500, false);
    
    if ($writeToFile) {
        foreach ($images as $i => $img)
            file_put_contents("plots/gen_$i.png", $img);
    }
    
    return $images;
}