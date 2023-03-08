<?php

namespace Waka\YamlForms;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Yaml\Yaml;

trait WakaFormProcessor
{

    /**
     * Extract nodes that have a "field" attribute, merging their data with their parent's data.
     *
     * @param array $data The data to process.
     * @return array The processed data.
     */
    public function extractFields(array $data): array
    {
        $processedData = [];

        foreach ($data as $key => $value) {

            if (isset($value['column'])) {
                unset($value['column']);
            }
            // If the node has a "column" attribute that is boolean false, skip it.
            if (isset($value['field']) && $value['field'] === false) {
                continue;
            }

            // If the node has a "column" attribute that is an array, merge its data with its parent's data.
            if (isset($value['field']) && is_array($value['field'])) {
                $value = array_merge_recursive($value, $value['field']);
                unset($value['field']);
            }

            // If the node has "datas" children, process them recursively.
            if (isset($value['datas'])) {
                $value['datas'] = $this->extractFields($value['datas']);
                // Remove the "datas" node if it has no "column" children.
                if (empty($value['datas'])) {
                    unset($value['datas']);
                }
            }

            // Add the processed node to the result.
            $processedData[$key] = $value;
        }

        return $processedData;
    }

    /**
     * Extract nodes that have a "column" attribute.
     *
     * @param array $data The data to process.
     * @return array The processed data.
     */
    public function extractColumns(array $data): array
    {
        $processedData = [];

        foreach ($data as $key => $value) {

            if (isset($value['field'])) {
                unset($value['field']);
            }
            // If the node has a "column" attribute that is boolean false, skip it.
            if (isset($value['column']) && $value['column'] === false) {
                continue;
            }

            // If the node has a "column" attribute that is an array, merge its data with its parent's data.
            if (isset($value['column']) && is_array($value['column'])) {
                $value = array_merge_recursive($value, $value['column']);
                unset($value['column']);
            }

            // If the node has "datas" children, process them recursively.
            if (isset($value['datas'])) {
                $value['datas'] = $this->extractColumns($value['datas']);
                // Remove the "datas" node if it has no "column" children.
                if (empty($value['datas'])) {
                    unset($value['datas']);
                }
            }

            // Add the processed node to the result.
            $processedData[$key] = $value;
        }

        return $processedData;
    }



    /**
     * Remove nodes that have a "context" attribute and whose value does not match the given context.
     *
     * @param array $data The data to process.
     * @param string|array $context The context to match.
     * @return array The processed data.
     */
    public function filterContext(array $data, $context): array
    {
        $processedData = [];

        foreach ($data as $key => $value) {
            // If the node has a "context" attribute and its value does not match the given context, skip it.
            if (isset($value['context']) && !in_array($context, (array)$value['context'], true)) {
                continue;
            }

            // If the node has "datas" children, process them recursively.
            if (isset($value['datas'])) {
                $value['datas'] = $this->filterContext($value['datas'], $context);
            }

            // Add the processed node to the result.
            $processedData[$key] = $value;
        }

        return $processedData;
    }
}
