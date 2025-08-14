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

    /**
     * 获取服务器IP地址
     * @return string
     */
    public static function getServerIp(): string
    {
        // 尝试多种方式获取服务器IP
        $ip = '';
        
        // 方式1：从服务器变量获取
        if (isset($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        }
        // 方式2：从本地主机名获取
        elseif (function_exists('gethostname')) {
            $hostname = gethostname();
            $ip = gethostbyname($hostname);
        }
        // 方式3：获取本地IP
        else {
            $ip = gethostbyname('localhost');
        }
        
        // 如果获取到的是127.0.0.1或0.0.0.0，尝试获取真实的本地IP
        if (in_array($ip, ['127.0.0.1', '0.0.0.0', ''])) {
            $ip = self::getLocalIp();
        }
        
        return $ip ?: '127.0.0.1';
    }
    
    /**
     * 获取本地真实IP地址
     * @return string
     */
    public static function getLocalIp(): string
    {
        // 尝试获取真实的本地IP
        $ip = '';
        
        // 在Windows系统上可以通过ipconfig命令获取
        if (function_exists('shell_exec') && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // 尝试使用ipconfig命令获取本地IP
            $output = shell_exec('ipconfig');
            if ($output) {
                // 匹配IPv4地址
                if (preg_match('/IPv4 地址[\. ]+: ([0-9\.]+)/', $output, $matches)) {
                    $ip = $matches[1];
                }
            }
        }
        // 在Linux系统上可以通过ifconfig或ip命令获取
        elseif (function_exists('shell_exec')) {
            // 尝试使用ip命令（Linux）
            $output = shell_exec('ip route get 1 | awk \'{print $NF;exit}\'');
            if ($output && trim($output) && filter_var(trim($output), FILTER_VALIDATE_IP)) {
                return trim($output);
            }
            
            // 尝试使用hostname -I命令（Linux）
            $output = shell_exec('hostname -I');
            if ($output && trim($output)) {
                $ips = explode(' ', trim($output));
                foreach ($ips as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP) && !in_array($ip, ['127.0.0.1', '0.0.0.0'])) {
                        return $ip;
                    }
                }
            }
        }
        
        // 如果以上方法都失败，返回127.0.0.1
        return '127.0.0.1';
    }


}