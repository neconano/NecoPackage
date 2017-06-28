<?php

namespace neco\String;

class Secret
{
    private $privateKeyPath = '';
    private $privateKeyPassword = '';
    private $publicKeyPath = '';
    private $password = '';

    /**
     * RC4 加密
     *
     * @author Cong Peijun <congpeijun@tuozhongedu.com>
     * @param  string $data
     */
    public function RC4Encode($data)
    {
        return openssl_encrypt($data, 'RC4', $this->password);
    }

    /**
     * RC4 解密
     *
     * @author Cong Peijun <congpeijun@tuozhongedu.com>
     * @param  string $data
     */
    public function RC4Decode($data)
    {
        return openssl_decrypt($data, 'RC4', $this->password);
    }

    private function getPublicKeyContent()
    {
        return file_get_contents($this->publicKeyPath);
    }

    private function getPrivateKeyContent()
    {
        return file_get_contents($this->privateKeyPath);
    }

    /**
     * 公钥加密加密密码
     *
     * @author Cong Peijun <congpeijun@tuozhongedu.com>
     * @param  string $text
     * @return string $crypttext
     */
    public function publicKeyEncode($text)
    {
        $res = openssl_pkey_get_public($this->getPublicKeyContent());
        openssl_public_encrypt($text, $crypttext, $res);
        return $crypttext;
    }

    /**
     * 通过加密字符串解密获取用户加密的字符串
     *
     * @author Cong Peijun <congpeijun@tuozhongedu.com>
     * @param  string $cryptText
     * @return string
     */
    public function privateKeyDecode($cryptText)
    {
        $res = openssl_pkey_get_private($this->getPrivateKeyContent(), $this->privateKeyPassword);
        openssl_private_decrypt($cryptText, $decryptText, $res);
        return $decryptText;
    }

    public function publicKeyDecode($cryptText)
    {
        $res = openssl_pkey_get_public($this->getPublicKeyContent());
        if (openssl_public_decrypt($cryptText, $text, $res)) {
            return $text;
        }
        throw new \Exception('Public key decode fail');
    }

    public function privateKeyEncode($text)
    {
        $res = openssl_pkey_get_private($this->getPrivateKeyContent(), $this->privateKeyPassword);
        if (openssl_private_encrypt($text, $crypted, $res)) {
            return $crypted;
        }

        throw new \Exception('Private key encode fail');
    }

    /**
     * Sets the value of privateKeyPath.
     *
     * @param mixed $privateKeyPath the private key path
     *
     * @return self
     */
    public function setPrivateKeyPath($privateKeyPath)
    {
        $this->privateKeyPath = $privateKeyPath;
        return $this;
    }

    /**
     * Sets the value of password.
     *
     * @param mixed $password the password
     *
     * @return self
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Sets the value of privateKeyPassword.
     *
     * @param mixed $privateKeyPassword the private key password
     *
     * @return self
     */
    public function setPrivateKeyPassword($privateKeyPassword)
    {
        $this->privateKeyPassword = $privateKeyPassword;
        return $this;
    }

    /**
     * Sets the value of publicKeyPath.
     *
     * @param mixed $publicKeyPath the public key path
     *
     * @return self
     */
    public function setPublicKeyPath($publicKeyPath)
    {
        $this->publicKeyPath = $publicKeyPath;
        return $this;
    }
}
