<?php


namespace app\common\support;
class Tool
{


    /**
     * 解析用户代理字符串
     * @param string $userAgent 用户代理字符串
     * @return array 解析后的信息
     */
    public function parseUserAgent(string $userAgent): array
    {
        $result = [
            'browser' => 'Unknown',
            'browser_version' => 'Unknown',
            'os' => 'Unknown',
            'os_version' => 'Unknown',
            'device' => 'Unknown'
        ];

        // 空字符串处理
        if (empty($userAgent)) {
            return $result;
        }

        // 解析浏览器信息
        if (preg_match('/Edg\/([\d.]+)/', $userAgent, $matches)) {
            $result['browser'] = 'Microsoft Edge';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/Chrome\/([\d.]+)/', $userAgent, $matches)) {
            $result['browser'] = 'Google Chrome';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/Firefox\/([\d.]+)/', $userAgent, $matches)) {
            $result['browser'] = 'Mozilla Firefox';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/Safari\/([\d.]+)/', $userAgent, $matches) && !preg_match('/Chrome/', $userAgent)) {
            $result['browser'] = 'Safari';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/MSIE ([\d.]+)/', $userAgent, $matches) || preg_match('/Trident\/.+?rv:([\d.]+)/', $userAgent, $matches)) {
            $result['browser'] = 'Internet Explorer';
            $result['browser_version'] = $matches[1];
        }

        // 解析操作系统信息
        if (preg_match('/Windows NT 10.0/', $userAgent)) {
            $result['os'] = 'Windows';
            $result['os_version'] = '10';
        } elseif (preg_match('/Windows NT 6.3/', $userAgent)) {
            $result['os'] = 'Windows';
            $result['os_version'] = '8.1';
        } elseif (preg_match('/Windows NT 6.2/', $userAgent)) {
            $result['os'] = 'Windows';
            $result['os_version'] = '8';
        } elseif (preg_match('/Windows NT 6.1/', $userAgent)) {
            $result['os'] = 'Windows';
            $result['os_version'] = '7';
        } elseif (preg_match('/Mac OS X ([\d_]+)/', $userAgent, $matches)) {
            $result['os'] = 'macOS';
            $result['os_version'] = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Linux/', $userAgent)) {
            $result['os'] = 'Linux';
        } elseif (preg_match('/Android ([\d.]+)/', $userAgent, $matches)) {
            $result['os'] = 'Android';
            $result['os_version'] = $matches[1];
            $result['device'] = 'Mobile';
        } elseif (preg_match('/iPhone OS ([\d_]+)/', $userAgent, $matches)) {
            $result['os'] = 'iOS';
            $result['os_version'] = str_replace('_', '.', $matches[1]);
            $result['device'] = 'Mobile';
        }

        // 判断设备类型
        if ($result['device'] == 'Unknown') {
            if (preg_match('/Mobile|Android|iPhone|iPad|iPod|Opera Mini|IEMobile/', $userAgent)) {
                $result['device'] = 'Mobile';
            } else {
                $result['device'] = 'Desktop';
            }
        }

        return $result;
    }



}