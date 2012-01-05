<?php
class AuthorAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalyzerData $data)
    {
        foreach($data->data as & $value)
            $value = strtok('/:#', $value);
    }
}