<?php
require '../vendor/autoload.php';

use sqonk\phext\datakit\{PackedSequence,PackedArray,DataFrame,Vector,Importer,SMA,EMA,GroupedDataFrame,DOMScraper};
use sqonk\phext\core\strings;


function formatComment($comment)
{
    $comment = trim(str_replace(['/**', '*/'], '', $comment));
    if (! $comment)
        $comment = "No documentation available.";
    else {
        $comment = str_replace('*', '', $comment);
        $comment = implode("\n", array_map(fn($line) => trim($line), explode("\n", $comment)));
        
        $comment = str_replace(['NULL', 'TRUE', 'FALSE'], ["`NULL`", "`TRUE`", "`FALSE`"], $comment);
        $comment = implode("\n\n", array_map(function($para) {
            if (! contains($para, '-- parameters:'))
            {
                // standard paragraph
                if (! starts_with(trim($para), '```'))
                    $para = str_replace(["\n", "\t", "@return"], [" ", " ", "**Returns:** "], $para);
                //$para = preg_replace('/\$(.+?)\b/i', "\$$1", $para);
            }
            else 
            {
                // parameter/option listing
                $lines = explode("\n", $para);
                $filtered = [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (! starts_with($line, '-- parameters:')) {
                        if (starts_with($line, '@param')) {
                            $line = '- **'.trim(substr($line, 7));
                            $line = str_replace("\t", " ", $line);
                            $line = substr_replace($line, '** ', strpos($line, ' ', 4), 1); 
                        }
                        else if ($line == '*')
                            $line = "";
                        else {
                            $line = str_replace(["----", "---", '--'], ["\t\t\t-", "\t\t-", "\t-"], $line);
                        }
                        if ($line)
                            $filtered[] = str_replace("\n", ' ', $line);
                    }
                }
                $para = implode("\n", $filtered);
            }
            return $para;
        }, explode("\n\n", $comment)));
    }
    return $comment;
}

function generateForClass($cl)
{
    $class = new ReflectionClass($cl);
    $name = $class->getShortName();
    
    $out = new SplFileObject(sprintf("%s/api/%s.md", __DIR__, $name), 'w+');
    $out->fwrite("###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > $name\n");
    $out->fwrite("------\n");
    $out->fwrite("### $name\n");
    $out->fwrite(formatComment($class->getDocComment())."\n");
    
    $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
    $out->fwrite("#### Methods\n");
    foreach ($methods as $m) {
        $out->fwrite(sprintf("[%s](#%s)\n", $m->getName(), str_replace(' ', '-', strtolower($m->getName()))));
    }
    $out->fwrite("\n------\n");
    
    foreach ($methods as $method)
    {
        $m = $method->getName();
        $out->fwrite("##### $m\n");
        $out->fwrite("```php\n");
        
        $params = [];
        foreach ($method->getParameters() as $p) {
            $str = '';
            if ($type = $p->getType())
                $str .= $type->getName()." ";
            
            if ($p->isPassedByReference())
                $str .= '&$'.$p->getName();
            else
                $str .= '$'.$p->getName();
            if ($p->isOptional() && $p->isDefaultValueAvailable()) {
                $def = $p->getDefaultValue();
                
                if (is_array($def))
                    $def = '['.implode(', ', $def).']';
                else if (is_string($def))
                    $def = "'$def'";
                else if ($p->isDefaultValueConstant())
                    $def = $p->getDefaultValueConstantName();
                else if (is_null($def))
                    $def = 'null';
                
                $str .= " = $def";
            }
            
            $params[] = $str;    
        }
        $params_str = implode(', ', $params);
        if ($rt = $method->getReturnType())
            $rt = ": $rt";
        $static = $method->isStatic() ? 'static ' : '';
        $out->fwrite("{$static}public function {$m}($params_str) $rt\n");
        $out->fwrite("```\n");
        
        $out->fwrite(formatComment($method->getDocComment())."\n\n\n------\n");
    }
    
}


function main()
{
    generateForClass(DataFrame::class);
    generateForClass(Vector::class);
    generateForClass(Importer::class);
    generateForClass(PackedArray::class);
    generateForClass(PackedSequence::class);
    generateForClass(SMA::class);
    generateForClass(EMA::class);
    generateForClass(GroupedDataFrame::class);
    generateForClass(DOMScraper::class);
}

main();